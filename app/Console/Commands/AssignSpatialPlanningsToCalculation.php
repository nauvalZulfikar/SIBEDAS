<?php

namespace App\Console\Commands;

use App\Models\SpatialPlanning;
use App\Models\RetributionCalculation;
use App\Models\BuildingType;
use App\Services\RetributionCalculatorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignSpatialPlanningsToCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spatial-planning:assign-calculations 
                            {--force : Force assign even if already has calculation}
                            {--recalculate : Recalculate existing calculations with new values}
                            {--chunk=100 : Process in chunks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign retribution calculations to spatial plannings (recalculate mode recalculates with current values)';

    protected $calculatorService;

    public function __construct(RetributionCalculatorService $calculatorService)
    {
        parent::__construct();
        $this->calculatorService = $calculatorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏗️  Starting spatial planning calculation assignment...');
        
        // Get processing options
        $force = $this->option('force');
        $recalculate = $this->option('recalculate');
        $chunkSize = (int) $this->option('chunk');
        
        // Get spatial plannings query
        $query = SpatialPlanning::query();
        
        if ($recalculate) {
            // Recalculate mode: only process those WITH active calculations
            $query->whereHas('retributionCalculations', function ($q) {
                $q->where('is_active', true);
            });
            $this->info('🔄 Recalculate mode: Processing spatial plannings with existing calculations');
            $this->warn('⚠️  NOTE: Recalculate mode will recalculate all existing calculations with current values');
        } elseif (!$force) {
            // Normal mode: only process those without active calculations
            $query->whereDoesntHave('retributionCalculations', function ($q) {
                $q->where('is_active', true);
            });
            $this->info('➕ Normal mode: Processing spatial plannings without calculations');
        } else {
            // Force mode: process all
            $this->info('🔥 Force mode: Processing ALL spatial plannings');
        }
        
        $totalRecords = $query->count();
        
        if ($totalRecords === 0) {
            $this->warn('No spatial plannings found to process.');
            return 0;
        }
        
        $this->info("Found {$totalRecords} spatial planning(s) to process");
        
        if (!$this->confirm('Do you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Process in chunks
        $processed = 0;
        $errors = 0;
        $reused = 0;
        $created = 0;
        $buildingTypeStats = [];
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();
        
        $recalculated = 0;
        
        $query->chunk($chunkSize, function ($spatialPlannings) use (&$processed, &$errors, &$reused, &$created, &$recalculated, &$buildingTypeStats, $progressBar, $recalculate) {
            foreach ($spatialPlannings as $spatialPlanning) {
                try {
                    $result = $this->assignCalculationToSpatialPlanning($spatialPlanning, $recalculate);
                    
                    if ($result['reused']) {
                        $reused++;
                    } elseif (isset($result['recalculated']) && $result['recalculated']) {
                        $recalculated++;
                    } else {
                        $created++;
                    }
                    
                    // Track building type statistics
                    $buildingTypeName = $result['building_type_name'] ?? 'Unknown';
                    if (!isset($buildingTypeStats[$buildingTypeName])) {
                        $buildingTypeStats[$buildingTypeName] = 0;
                    }
                    $buildingTypeStats[$buildingTypeName]++;
                    
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error processing ID {$spatialPlanning->id}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        
        // Show summary
        $this->newLine(2);
        $this->info('✅ Assignment completed!');
        
        if ($recalculate) {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Processed', $processed],
                    ['Recalculated (Changed)', $recalculated],
                    ['Unchanged', $reused],
                    ['Errors', $errors],
                ]
            );
            $this->info('📊 Recalculate mode recalculated all existing calculations with current values');
        } else {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Processed', $processed],
                    ['Calculations Created', $created],
                    ['Calculations Reused', $reused],
                    ['Errors', $errors],
                ]
            );
        }
        
        // Show building type statistics
        if (!empty($buildingTypeStats)) {
            $this->newLine();
            $this->info('📊 Building Type Distribution:');
            $statsRows = [];
            arsort($buildingTypeStats); // Sort by count descending
            foreach ($buildingTypeStats as $typeName => $count) {
                $percentage = round(($count / $processed) * 100, 1);
                $statsRows[] = [$typeName, $count, $percentage . '%'];
            }
            $this->table(['Building Type', 'Count', 'Percentage'], $statsRows);
        }
        
        return 0;
    }
    
    /**
     * Assign calculation to a spatial planning
     */
    private function assignCalculationToSpatialPlanning(SpatialPlanning $spatialPlanning, bool $recalculate = false): array
    {
        // 1. Detect building type
        $buildingType = $this->detectBuildingType($spatialPlanning->building_function);
        
        // 2. Get calculation parameters (round to 2 decimal places)
        $floorNumber = $spatialPlanning->number_of_floors ?: 1;
        $buildingArea = round($spatialPlanning->getCalculationArea(), 2);
        
        if ($buildingArea <= 0) {
            throw new \Exception("Invalid building area: {$buildingArea}");
        }
        
        $reused = false;
        $isRecalculated = false;
        
        if ($recalculate) {
            // Recalculate mode: Always create new calculation
            $calculationResult = $this->performCalculation($spatialPlanning, $buildingType, true);
            
            // Check if spatial planning has existing active calculation
            $currentActiveCalculation = $spatialPlanning->activeRetributionCalculation;
            
            if ($currentActiveCalculation) {
                $oldAmount = $currentActiveCalculation->retributionCalculation->retribution_amount;
                $oldArea = $currentActiveCalculation->retributionCalculation->building_area;
                $newAmount = $calculationResult['amount'];
                
                // Check if there's a significant difference (more than 1 rupiah)
                if (abs($oldAmount - $newAmount) > 1) {
                    // Create new calculation
                    $calculation = RetributionCalculation::create([
                        'building_type_id' => $buildingType->id,
                        'floor_number' => $floorNumber,
                        'building_area' => $buildingArea,
                        'retribution_amount' => $calculationResult['amount'],
                        'calculation_detail' => $calculationResult['detail'],
                    ]);
                    
                    // Assign new calculation
                    $spatialPlanning->assignRetributionCalculation(
                        $calculation,
                        "Recalculated: Original area {$oldArea}m² → New area {$buildingArea}m², Amount {$oldAmount}→{$newAmount}"
                    );
                    
                    $isRecalculated = true;
                } else {
                    // No significant difference, keep existing
                    $calculation = $currentActiveCalculation->retributionCalculation;
                    $reused = true;
                }
            } else {
                // No existing calculation, create new
                $calculation = RetributionCalculation::create([
                    'building_type_id' => $buildingType->id,
                    'floor_number' => $floorNumber,
                    'building_area' => $buildingArea,
                    'retribution_amount' => $calculationResult['amount'],
                    'calculation_detail' => $calculationResult['detail'],
                ]);
                
                $spatialPlanning->assignRetributionCalculation(
                    $calculation,
                    'Recalculated (new calculation with current values)'
                );
            }
        } else {
            // Normal mode: Check if calculation already exists with same parameters
            $existingCalculation = RetributionCalculation::where([
                'building_type_id' => $buildingType->id,
                'floor_number' => $floorNumber,
            ])
            ->whereBetween('building_area', [
                $buildingArea * 0.99, // 1% tolerance
                $buildingArea * 1.01
            ])
            ->first();
            
            if ($existingCalculation) {
                // Reuse existing calculation
                $calculation = $existingCalculation;
                $reused = true;
            } else {
                // Create new calculation
                $calculationResult = $this->performCalculation($spatialPlanning, $buildingType, false);
                
                $calculation = RetributionCalculation::create([
                    'building_type_id' => $buildingType->id,
                    'floor_number' => $floorNumber,
                    'building_area' => $buildingArea,
                    'retribution_amount' => $calculationResult['amount'],
                    'calculation_detail' => $calculationResult['detail'],
                ]);
            }
            
            // Assign to spatial planning
            $spatialPlanning->assignRetributionCalculation(
                $calculation,
                $reused ? 'Auto-assigned (reused calculation)' : 'Auto-assigned (new calculation)'
            );
        }
        
        return [
            'calculation' => $calculation,
            'reused' => $reused,
            'recalculated' => $isRecalculated,
            'building_type_name' => $buildingType->name,
            'building_type_code' => $buildingType->code,
        ];
    }
    
    /**
     * Detect building type based on building function using database
     */
    private function detectBuildingType(string $buildingFunction = null): BuildingType
    {
        $function = strtolower($buildingFunction ?? '');
        
        // Mapping building functions to building type codes from database
        $mappings = [
            // Religious
            'masjid' => 'KEAGAMAAN',
            'gereja' => 'KEAGAMAAN', 
            'vihara' => 'KEAGAMAAN',
            'pura' => 'KEAGAMAAN',
            'keagamaan' => 'KEAGAMAAN',
            'religious' => 'KEAGAMAAN',
            
            // Residential/Housing
            'rumah' => 'HUN_SEDH', // Default to simple housing
            'perumahan' => 'HUN_SEDH',
            'hunian' => 'HUN_SEDH',
            'residential' => 'HUN_SEDH',
            'tinggal' => 'HUN_SEDH',
            'mbr' => 'MBR', // Specifically for MBR
            'masyarakat berpenghasilan rendah' => 'MBR',
            
            // Commercial/Business - default to UMKM
            'toko' => 'UMKM',
            'warung' => 'UMKM',
            'perdagangan' => 'UMKM',
            'dagang' => 'UMKM',
            'usaha' => 'UMKM',
            'komersial' => 'UMKM',
            'commercial' => 'UMKM',
            'pasar' => 'UMKM',
            'kios' => 'UMKM',
            
            // Large commercial
            'mall' => 'USH_BESAR',
            'plaza' => 'USH_BESAR',
            'supermarket' => 'USH_BESAR',
            'department' => 'USH_BESAR',
            'hotel' => 'USH_BESAR',
            'resort' => 'USH_BESAR',
            
            // Office
            'kantor' => 'UMKM', // Can be UMKM or USH_BESAR depending on size
            'perkantoran' => 'UMKM',
            'office' => 'UMKM',
            
            // Industry (usually big business)
            'industri' => 'USH_BESAR',
            'pabrik' => 'USH_BESAR',
            'gudang' => 'USH_BESAR',
            'warehouse' => 'USH_BESAR',
            'manufacturing' => 'USH_BESAR',
            
            // Social/Cultural
            'sekolah' => 'SOSBUDAYA',
            'pendidikan' => 'SOSBUDAYA',
            'universitas' => 'SOSBUDAYA',
            'kampus' => 'SOSBUDAYA',
            'rumah sakit' => 'SOSBUDAYA',
            'klinik' => 'SOSBUDAYA',
            'kesehatan' => 'SOSBUDAYA',
            'puskesmas' => 'SOSBUDAYA',
            'museum' => 'SOSBUDAYA',
            'perpustakaan' => 'SOSBUDAYA',
            'gedung olahraga' => 'SOSBUDAYA',
            
            // Mixed use
            'campuran' => 'CAMP_KECIL', // Default to small mixed
            'mixed' => 'CAMP_KECIL',
        ];
        
        // Try to match building function
        $detectedCode = null;
        foreach ($mappings as $keyword => $code) {
            if (str_contains($function, $keyword)) {
                $detectedCode = $code;
                break;
            }
        }
        
        // Find building type in database by code
        if ($detectedCode) {
            $buildingType = BuildingType::where('code', $detectedCode)
                                      ->whereHas('indices') // Only types with indices
                                      ->first();
            
            if ($buildingType) {
                return $buildingType;
            }
        }
        
        // Default to "UMKM" type if not detected (most common business type)
        $defaultType = BuildingType::where('code', 'UMKM')
                                  ->whereHas('indices')
                                  ->first();
        
        if ($defaultType) {
            return $defaultType;
        }
        
        // Fallback to any available type with indices
        $fallbackType = BuildingType::whereHas('indices')
                                   ->where('is_active', true)
                                   ->first();
        
        if (!$fallbackType) {
            throw new \Exception('No building types with indices found in database. Please run: php artisan db:seed --class=RetributionDataSeeder');
        }
        
        return $fallbackType;
    }
    
    /**
     * Perform calculation using RetributionCalculatorService
     */
    private function performCalculation(SpatialPlanning $spatialPlanning, BuildingType $buildingType, bool $recalculate = false): array
    {
        // Round area to 2 decimal places to match database storage format
        $buildingArea = round($spatialPlanning->getCalculationArea(), 2);
        
        // For recalculate mode, use the current area without any adjustment
        if ($recalculate) {
            $this->info("Recalculate mode: Using current area {$buildingArea}m²");
        }
        
        $floorNumber = $spatialPlanning->number_of_floors ?: 1;
        
        try {
            // Use the same calculation service as TestRetributionCalculation
            $result = $this->calculatorService->calculate(
                $buildingType->id, 
                $floorNumber, 
                $buildingArea, 
                false // Don't save to database, we'll handle that separately
            );
            
            return [
                'amount' => $result['total_retribution'],
                'detail' => [
                    'building_type_id' => $buildingType->id,
                    'building_type_name' => $buildingType->name,
                    'building_type_code' => $buildingType->code,
                    'coefficient' => $result['indices']['coefficient'],
                    'ip_permanent' => $result['indices']['ip_permanent'],
                    'ip_complexity' => $result['indices']['ip_complexity'],
                    'locality_index' => $result['indices']['locality_index'],
                    'height_index' => $result['input_parameters']['height_index'],
                    'infrastructure_factor' => $result['indices']['infrastructure_factor'],
                    'building_area' => $buildingArea,
                    'floor_number' => $floorNumber,
                    'building_function' => $spatialPlanning->building_function,
                    'calculation_steps' => $result['calculation_detail'],
                    'base_value' => $result['input_parameters']['base_value'],
                    'is_free' => $buildingType->is_free,
                    'calculation_date' => now()->toDateTimeString(),
                    'total' => $result['total_retribution'],
                    'is_recalculated' => $recalculate,
                ]
            ];
            
        } catch (\Exception $e) {
            // Fallback to basic calculation if service fails
            $this->warn("Calculation service failed for {$spatialPlanning->name}: {$e->getMessage()}. Using fallback calculation.");
            
            // Basic fallback calculation
            $totalAmount = $buildingType->is_free ? 0 : ($buildingArea * 50000);
            
            // For recalculate mode in fallback, use current amount without adjustment
            if ($recalculate) {
                $this->warn("Fallback recalculate: Using current amount Rp{$totalAmount}");
            }
            
            return [
                'amount' => $totalAmount,
                'detail' => [
                    'building_type_id' => $buildingType->id,
                    'building_type_name' => $buildingType->name,
                    'building_type_code' => $buildingType->code,
                    'building_area' => $buildingArea,
                    'floor_number' => $floorNumber,
                    'building_function' => $spatialPlanning->building_function,
                    'calculation_method' => 'fallback',
                    'error_message' => $e->getMessage(),
                    'is_free' => $buildingType->is_free,
                    'calculation_date' => now()->toDateTimeString(),
                    'total' => $totalAmount,
                    'is_recalculated' => $recalculate,
                ]
            ];
        }
    }
}
