<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetributionCalculatorService;
use App\Models\BuildingType;

class TestRetributionCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retribution:test 
                            {--area= : Luas bangunan dalam m2}
                            {--floor= : Jumlah lantai (1-6)}
                            {--type= : ID atau kode building type}
                            {--all : Test semua building types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test perhitungan retribusi PBG dengan input luas bangunan dan tinggi lantai';

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
        $this->info('🏢 SISTEM TEST PERHITUNGAN RETRIBUSI PBG');
        $this->info('=' . str_repeat('=', 50));

        // Test all building types if --all flag is used
        if ($this->option('all')) {
            return $this->testAllBuildingTypes();
        }

        // Get input parameters
        $area = $this->getArea();
        $floor = $this->getFloor();
        $buildingTypeId = $this->getBuildingType();

        if (!$area || !$floor || !$buildingTypeId) {
            $this->error('❌ Parameter tidak lengkap!');
            return 1;
        }

        // Perform calculation
        $this->performCalculation($buildingTypeId, $floor, $area);

        return 0;
    }

    protected function getArea()
    {
        $area = $this->option('area');
        
        if (!$area) {
            $area = $this->ask('📐 Masukkan luas bangunan (m²)');
        }

        if (!is_numeric($area) || $area <= 0) {
            $this->error('❌ Luas bangunan harus berupa angka positif!');
            return null;
        }

        return (float) $area;
    }

    protected function getFloor()
    {
        $floor = $this->option('floor');
        
        if (!$floor) {
            $floor = $this->ask('🏗️ Masukkan jumlah lantai (1-6)');
        }

        if (!is_numeric($floor) || $floor < 1 || $floor > 6) {
            $this->error('❌ Jumlah lantai harus antara 1-6!');
            return null;
        }

        return (int) $floor;
    }

    protected function getBuildingType()
    {
        $type = $this->option('type');
        
        if (!$type) {
            $this->showBuildingTypes();
            $type = $this->ask('🏢 Masukkan ID atau kode building type');
        }

        // Try to find by ID first, then by code
        $buildingType = null;
        
        if (is_numeric($type)) {
            $buildingType = BuildingType::find($type);
        } else {
            $buildingType = BuildingType::where('code', strtoupper($type))->first();
        }

        if (!$buildingType) {
            $this->error('❌ Building type tidak ditemukan!');
            return null;
        }

        return $buildingType->id;
    }

    protected function showBuildingTypes()
    {
        $this->info('📋 DAFTAR BUILDING TYPES:');
        $this->line('');

        $buildingTypes = BuildingType::with('indices')
            ->whereHas('indices') // Only types that have indices
            ->get();

        $headers = ['ID', 'Kode', 'Nama', 'Coefficient', 'Free'];
        $rows = [];

        foreach ($buildingTypes as $type) {
            $rows[] = [
                $type->id,
                $type->code,
                $type->name,
                $type->indices ? number_format($type->indices->coefficient, 4) : 'N/A',
                $type->is_free ? '✅' : '❌'
            ];
        }

        $this->table($headers, $rows);
        $this->line('');
    }

    protected function performCalculation($buildingTypeId, $floor, $area)
    {
        try {
            // Round area to 2 decimal places to match database storage format
            $roundedArea = round($area, 2);
            $result = $this->calculatorService->calculate($buildingTypeId, $floor, $roundedArea, false);
            
            $this->displayResults($result, $roundedArea, $floor);
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    protected function displayResults($result, $area, $floor)
    {
        $this->info('');
        $this->info('📊 HASIL PERHITUNGAN RETRIBUSI');
        $this->info('=' . str_repeat('=', 40));
        
        // Building info
        $this->line('🏢 <fg=cyan>Building Type:</> ' . $result['building_type']['name']);
        $this->line('📐 <fg=cyan>Luas Bangunan:</> ' . number_format($area, 0) . ' m²');
        $this->line('🏗️ <fg=cyan>Jumlah Lantai:</> ' . $floor);
        
        if (isset($result['building_type']['is_free']) && $result['building_type']['is_free']) {
            $this->line('');
            $this->info('🎉 GRATIS - Building type ini tidak dikenakan retribusi');
            $this->line('💰 <fg=green>Total Retribusi: Rp 0</fg=green>');
            return;
        }

        $this->line('');
        
        // Parameters
        $this->info('📋 PARAMETER PERHITUNGAN:');
        $indices = $result['indices'];
        $this->line('• Coefficient: ' . number_format($indices['coefficient'], 4));
        $this->line('• IP Permanent: ' . number_format($indices['ip_permanent'], 4));
        $this->line('• IP Complexity: ' . number_format($indices['ip_complexity'], 4));
        $this->line('• Locality Index: ' . number_format($indices['locality_index'], 4));
        $this->line('• Height Index: ' . number_format($result['input_parameters']['height_index'], 4));
        
        $this->line('');
        
        // Calculation steps
        $this->info('🔢 LANGKAH PERHITUNGAN:');
        $detail = $result['calculation_detail'];
        $this->line('1. H5 Raw: ' . number_format($detail['h5_raw'], 6));
        $this->line('2. H5 Rounded: ' . number_format($detail['h5'], 4));
        $this->line('3. Main Calculation: Rp ' . number_format($detail['main'], 2));
        $this->line('4. Infrastructure (50%): Rp ' . number_format($detail['infrastructure'], 2));
        
        $this->line('');
        
        // Final result
        $this->info('💰 <fg=green>TOTAL RETRIBUSI: ' . $result['formatted_amount'] . '</fg=green>');
        $this->line('📈 <fg=yellow>Per m²: Rp ' . number_format($result['total_retribution'] / $area, 2) . '</fg=yellow>');
    }

    protected function testAllBuildingTypes()
    {
        $area = round($this->option('area') ?: 100, 2);
        $floor = $this->option('floor') ?: 2;
        
        $this->info("🧪 TESTING SEMUA BUILDING TYPES");
        $this->info("📐 Luas: {$area} m² | 🏗️ Lantai: {$floor}");
        $this->info('=' . str_repeat('=', 60));
        
        $buildingTypes = BuildingType::with('indices')
            ->whereHas('indices') // Only types that have indices
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $headers = ['Kode', 'Nama', 'Coefficient', 'Total Retribusi', 'Per m²'];
        $rows = [];

        foreach ($buildingTypes as $type) {
            try {
                $result = $this->calculatorService->calculate($type->id, $floor, $area, false);
                
                if ($type->is_free) {
                    $rows[] = [
                        $type->code,
                        $type->name,
                        'FREE',
                        'Rp 0',
                        'Rp 0'
                    ];
                } else {
                    $rows[] = [
                        $type->code,
                        $type->name,
                        number_format($result['indices']['coefficient'], 4),
                        'Rp ' . number_format($result['total_retribution'], 0),
                        'Rp ' . number_format($result['total_retribution'] / $area, 0)
                    ];
                }
            } catch (\Exception $e) {
                $rows[] = [
                    $type->code,
                    $type->name,
                    'ERROR',
                    $e->getMessage(),
                    '-'
                ];
            }
        }

        $this->table($headers, $rows);
        
        return 0;
    }
}
