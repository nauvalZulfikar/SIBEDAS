<?php

namespace App\Console\Commands;

use App\Models\SpatialPlanning;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class InjectSpatialPlanningsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spatial-planning:inject 
                            {--file=storage/app/public/templates/2025.xlsx : Path to Excel file}
                            {--sheet=0 : Sheet index to read from}
                            {--dry-run : Run without actually inserting data}
                            {--debug : Show Excel content for debugging}
                            {--truncate : Clear existing data before import}
                            {--no-truncate : Skip truncation (keep existing data)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inject spatial planning data from Excel file with BCR area calculation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $filePath = $this->option('file');
            $sheetIndex = (int) $this->option('sheet');
            $isDryRun = $this->option('dry-run');
            $isDebug = $this->option('debug');
            $shouldTruncate = $this->option('truncate');
            $noTruncate = $this->option('no-truncate');

            if (!file_exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return 1;
            }

            $this->info("Reading Excel file: {$filePath}");
            $this->info("Sheet index: {$sheetIndex}");
            
            if ($isDryRun) {
                $this->warn("DRY RUN MODE - No data will be inserted");
            }

            // Check existing data
            $existingCount = DB::table('spatial_plannings')->count();
            if ($existingCount > 0) {
                $this->info("Found {$existingCount} existing spatial planning records");
            } else {
                $this->info('No existing spatial planning data found');
            }
            
            // Handle truncation logic
            $willTruncate = false;
            
            if ($shouldTruncate) {
                $willTruncate = true;
                $this->info('Truncation requested via --truncate option');
            } elseif ($noTruncate) {
                $willTruncate = false;
                $this->info('Truncation skipped via --no-truncate option');
            } else {
                // Default behavior: ask user if not in dry run mode
                if (!$isDryRun) {
                    $willTruncate = $this->confirm('Do you want to clear existing spatial planning data before import?');
                } else {
                    $willTruncate = false;
                    $this->info('DRY RUN MODE - Truncation will be skipped');
                }
            }
            
            // Confirm truncation if not in dry run mode and truncation is requested
            if ($willTruncate && !$isDryRun) {
                if (!$this->confirm('This will delete all existing spatial planning data and related retribution calculations. Continue?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }

            // Truncate all related data properly
            if ($willTruncate && !$isDryRun) {
                $this->info('Truncating spatial planning data and related retribution calculations...');
                
                try {
                    // Disable foreign key checks for safe truncation
                    DB::statement('SET FOREIGN_KEY_CHECKS = 0');
                    
                    // 1. Delete calculable retributions for spatial plannings (polymorphic relationship)
                    $deletedCalculableRetributions = DB::table('calculable_retributions')
                        ->where('calculable_type', 'App\\Models\\SpatialPlanning')
                        ->count();
                    
                    if ($deletedCalculableRetributions > 0) {
                        DB::table('calculable_retributions')
                            ->where('calculable_type', 'App\\Models\\SpatialPlanning')
                            ->delete();
                        $this->info("Deleted {$deletedCalculableRetributions} calculable retributions for spatial plannings.");
                    }
                    
                    // 2. Truncate spatial plannings table
                    DB::table('spatial_plannings')->truncate();
                    $this->info('Spatial plannings table truncated successfully.');
                    
                    // Re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS = 1');
                    
                    $this->info('All spatial planning data and related retribution calculations cleared successfully.');
                    
                } catch (Exception $e) {
                    // Make sure to re-enable foreign key checks even on error
                    try {
                        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
                    } catch (Exception $fkError) {
                        $this->error('Failed to re-enable foreign key checks: ' . $fkError->getMessage());
                    }
                    
                    $this->error('Failed to truncate spatial planning data: ' . $e->getMessage());
                    return 1;
                }
            } elseif ($willTruncate && $isDryRun) {
                $this->info('DRY RUN MODE - Would truncate spatial planning data and related retribution calculations');
            } else {
                $this->info('Keeping existing data (no truncation)');
            }

            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getSheet($sheetIndex);
            $rows = $worksheet->toArray(null, true, true, true);

            if ($isDebug) {
                $this->info("=== EXCEL CONTENT DEBUG ===");
                foreach (array_slice($rows, 0, 20) as $index => $row) {
                    if (!empty(array_filter($row))) {
                        $this->line("Row $index: " . json_encode($row));
                    }
                }
                $this->info("=== END DEBUG ===");
            }

            // Find BCR percentages from last rows (columns D and E)
            $bcrPercentages = $this->findBcrPercentages($rows);
            $this->info("Found BCR Percentages: " . json_encode($bcrPercentages));

            // Process data by sections
            $sections = $this->processSections($rows, $bcrPercentages, $isDebug);
            
            $this->info("Found " . count($sections) . " sections");

            $totalInserted = 0;
            foreach ($sections as $sectionIndex => $section) {
                $this->info("Processing Section " . ($sectionIndex + 1) . ": " . $section['applicant_name']);
                
                // Gudang/pergudangan keywords successfully added to Fungsi Usaha classification
                
                if (!$isDryRun) {
                    $inserted = $this->insertSpatialPlanningData($section);
                    $totalInserted += $inserted;
                    $this->info("Inserted {$inserted} record for this section");
                } else {
                    $this->info("Would insert 1 record for this section");
                }
            }

            if (!$isDryRun) {
                $this->info("Successfully inserted {$totalInserted} spatial planning records");
                
                // Show summary of what was done
                $finalCount = DB::table('spatial_plannings')->count();
                $this->info("Final spatial planning records count: {$finalCount}");
                
                if ($willTruncate) {
                    $this->info("✅ Data import completed with truncation");
                } else {
                    $this->info("✅ Data import completed (existing data preserved)");
                }
            } else {
                $this->info("Dry run completed. Total records that would be inserted: " . count($sections));
                if ($willTruncate) {
                    $this->info("Would truncate existing data before import");
                } else {
                    $this->info("Would preserve existing data during import");
                }
            }

            return 0;
        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("InjectSpatialPlanningsData failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Find BCR percentages from last rows in columns D and E
     */
    private function findBcrPercentages(array $rows): array
    {
        $bcrPercentages = [];
        
        // Look for BCR percentages in the last few rows
        $totalRows = count($rows);
        $searchRows = max(1, $totalRows - 10); // Search last 10 rows
        
        for ($i = $totalRows; $i >= $searchRows; $i--) {
            if (isset($rows[$i]['D']) && isset($rows[$i]['E'])) {
                $valueD = $this->cleanNumericValue($rows[$i]['D']);
                $valueE = $this->cleanNumericValue($rows[$i]['E']);
                
                // Check if these look like percentages (between 0 and 100)
                if ($valueD > 0 && $valueD <= 100 && $valueE > 0 && $valueE <= 100) {
                    $bcrPercentages['D'] = $valueD;
                    $bcrPercentages['E'] = $valueE;
                    break;
                }
            }
        }
        
        // Default values if not found
        if (empty($bcrPercentages)) {
            $bcrPercentages = ['D' => 60, 'E' => 40]; // Default BCR percentages
        }
        
        return $bcrPercentages;
    }

    /**
     * Process data by sections (each applicant)
     */
    private function processSections(array $rows, array $bcrPercentages, bool $isDebug): array
    {
        $sections = [];
        $currentSection = null;
        $currentSectionNumber = null;
        $sectionData = [];
        
        foreach ($rows as $rowIndex => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            if ($isDebug) {
                $this->line("Checking row $rowIndex: " . substr(json_encode($row), 0, 100) . "...");
            }
            
            // Check if this is a new section (applicant)
            if ($this->isNewSection($row)) {
                if ($isDebug) {
                    $this->info("Found new section at row $rowIndex");
                }
                
                // Save previous section if exists
                if ($currentSection && !empty($sectionData)) {
                    $sections[] = [
                        'applicant_name' => $currentSection,
                        'section_number' => $currentSectionNumber,
                        'data' => $sectionData
                    ];
                    if ($isDebug) {
                        $this->info("Saved section: $currentSection with " . count($sectionData) . " data rows");
                    }
                }
                
                // Start new section
                $currentSectionNumber = trim($row['A'] ?? ''); // Store section number
                $currentSection = $this->extractApplicantName($row);
                $sectionData = [];
                
                // Also process the header row itself for F, G, H data
                $headerRow = $this->processDataRow($row, $bcrPercentages);
                if ($headerRow) {
                    $sectionData[] = $headerRow;
                }
                
                if ($isDebug) {
                    $this->info("Starting new section: $currentSection");
                    $this->line("  Header F: " . ($row['F'] ?? 'null'));
                    $this->line("  Header G: " . ($row['G'] ?? 'null'));
                    $this->line("  Header H: " . ($row['H'] ?? 'null'));
                }
            } elseif ($currentSection && $this->isDataRow($row)) {
                if ($isDebug) {
                    $this->line("Found data row for section: $currentSection");
                    $this->line("  Column D: " . ($row['D'] ?? 'null'));
                    $this->line("  Column E: " . ($row['E'] ?? 'null'));
                    $this->line("  Column F: " . ($row['F'] ?? 'null'));
                    $this->line("  Column G: " . ($row['G'] ?? 'null'));
                    $this->line("  Column H: " . ($row['H'] ?? 'null'));
                }
                
                // Add data to current section
                $processedRow = $this->processDataRow($row, $bcrPercentages);
                if ($processedRow) {
                    $sectionData[] = $processedRow;
                }
            }
        }
        
        // Add last section
        if ($currentSection && !empty($sectionData)) {
            $sections[] = [
                'applicant_name' => $currentSection,
                'section_number' => $currentSectionNumber,
                'data' => $sectionData
            ];
        }
        
        return $sections;
    }

    /**
     * Check if row indicates a new section/applicant
     */
    private function isNewSection(array $row): bool
    {
        // Look for patterns that indicate a new applicant
        $firstCell = trim($row['A'] ?? '');
        
        // Check for pattern like "55 / 1565", "56 / 1543", etc.
        return !empty($firstCell) && preg_match('/^\d+\s*\/\s*\d+$/', $firstCell);
    }

    /**
     * Extract applicant name from section header
     */
    private function extractApplicantName(array $row): string
    {
        // Row A contains number like "55 / 1565", Row B contains name and phone
        $numberPart = trim($row['A'] ?? '');
        $namePart = trim($row['B'] ?? '');
        
        // Extract name from column B (remove phone number part)
        if (!empty($namePart)) {
            // Remove phone number pattern "No Telpon : xxxxx"
            $name = preg_replace('/\s*No Telpon\s*:\s*[\d\s\-\+\(\)]+.*$/i', '', $namePart);
            $name = trim($name);
            
            return !empty($name) ? $name : $numberPart;
        }
        
        return $numberPart ?: 'Unknown Applicant';
    }

    /**
     * Check if row contains data
     */
    private function isDataRow(array $row): bool
    {
        // Check if row has data we're interested in
        $columnD = trim($row['D'] ?? '');
        $columnE = trim($row['E'] ?? '');
        $columnF = trim($row['F'] ?? '');
        $columnG = trim($row['G'] ?? '');
        $columnH = trim($row['H'] ?? '');
        
        // Look for important data patterns in column D
        $importantPatterns = [
            'A. Total luas lahan',
            'Total luas lahan',
            'Total Luas Lahan',
            'BCR Kawasan',
            'E. BCR Kawasan',
            'D. BCR Kawasan',
            'KWT',
            'Total KWT',
            'KWT Perumahan',
            'D. KWT Perumahan',
            'E. KWT Perumahan',
            'BCR',
            'Koefisien Wilayah Terbangun'
        ];
        
        foreach ($importantPatterns as $pattern) {
            if (stripos($columnD, $pattern) !== false && !empty($columnE)) {
                return true;
            }
        }
        
        // Also check for location data
        if (stripos($columnD, 'Desa') !== false) {
            return true;
        }
        
        // Check if any of the important columns (F, G, H) have data
        // We want to capture ALL non-empty data in these columns within a section
        if (!empty($columnF) && trim($columnF) !== '') {
            return true;
        }
        if (!empty($columnG) && trim($columnG) !== '') {
            return true;
        }
        if (!empty($columnH) && trim($columnH) !== '') {
            return true;
        }
        
        return false;
    }

    /**
     * Process a data row and calculate area using BCR formula
     */
    private function processDataRow(array $row, array $bcrPercentages): ?array
    {
        try {
            $columnD = trim($row['D'] ?? '');
            $columnE = trim($row['E'] ?? '');
            $columnF = trim($row['F'] ?? '');
            $columnG = trim($row['G'] ?? '');
            $columnH = trim($row['H'] ?? '');
            
            $landArea = 0;
            $bcrPercentage = $bcrPercentages['D'] ?? 60; // Default BCR percentage
            $location = '';
            
            // Extract land area if this is a "Total luas lahan" row
            if (stripos($columnD, 'Total luas lahan') !== false || 
                stripos($columnD, 'A. Total luas lahan') !== false) {
                $landArea = $this->cleanNumericValue($columnE);
            }
            
            // Extract BCR percentage if this is a BCR row - comprehensive detection
            if (stripos($columnD, 'BCR Kawasan') !== false || 
                stripos($columnD, 'E. BCR Kawasan') !== false ||
                stripos($columnD, 'D. BCR Kawasan') !== false ||
                stripos($columnD, 'KWT Perumahan') !== false ||
                stripos($columnD, 'D. KWT Perumahan') !== false ||
                stripos($columnD, 'E. KWT Perumahan') !== false ||
                stripos($columnD, 'KWT') !== false ||
                (stripos($columnD, 'BCR') !== false && stripos($columnE, '%') !== false) ||
                stripos($columnD, 'Koefisien Wilayah Terbangun') !== false) {
                $bcrValue = $this->cleanNumericValue($columnE);
                if ($bcrValue > 0 && $bcrValue <= 100) {
                    $bcrPercentage = $bcrValue;
                }
            }
            
            // Get location from village/subdistrict info (previous rows in the section)
            if (stripos($columnD, 'Desa') !== false) {
                $location = $columnD;
            }
            
            // Calculate area: total luas lahan dikali persentase BCR
            $calculatedArea = $landArea > 0 && $bcrPercentage > 0 ? 
                              round($landArea * ($bcrPercentage / 100), 2) : 0;
            
            return [
                'data_type' => $columnD,
                'value' => $columnE,
                'land_area' => $landArea,
                'bcr_percentage' => $bcrPercentage,
                'calculated_area' => $calculatedArea,
                'location' => $location,
                'no_tapak' => !empty($columnF) ? $columnF : null,
                'no_skkl' => !empty($columnG) ? $columnG : null,
                'no_ukl' => !empty($columnH) ? $columnH : null,
                'raw_data' => $row
            ];
        } catch (Exception $e) {
            Log::warning("Error processing row", ['row' => $row, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Insert spatial planning data
     */
    private function insertSpatialPlanningData(array $section): int
    {
        try {
            // Process section data to extract key values
            $sectionData = $this->consolidateSectionData($section);
            
            if (empty($sectionData) || !$sectionData['has_valid_data']) {
                $this->warn("No valid data found for section: " . $section['applicant_name']);
                return 0;
            }
            
            SpatialPlanning::create([
                'name' => $section['applicant_name'],
                'number' => $section['section_number'], // Kolom A - section number
                'location' => $sectionData['location'], // Column C from header row
                'land_area' => $sectionData['land_area'],
                'area' => $sectionData['calculated_area'],
                'building_function' => $sectionData['building_function'], // Determined from activities
                'sub_building_function' => $sectionData['sub_building_function'], // UMKM or Usaha Besar
                'activities' => $sectionData['activities'], // Activities from column D of first row
                'site_bcr' => $sectionData['bcr_percentage'],
                'no_tapak' => $sectionData['no_tapak'],
                'no_skkl' => $sectionData['no_skkl'],
                'no_ukl' => $sectionData['no_ukl'],
                'date' => now()->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return 1;
        } catch (Exception $e) {
            Log::error("Error inserting spatial planning data", [
                'section' => $section['applicant_name'],
                'error' => $e->getMessage()
            ]);
            $this->warn("Failed to insert record for: " . $section['applicant_name']);
            return 0;
        }
    }
    
    /**
     * Consolidate section data into a single record
     */
    private function consolidateSectionData(array $section): array
    {
        $landArea = 0;
        $bcrPercentage = 60; // Default from Excel file
        $location = '';
        $activities = ''; // Activities from column D of first row
        $villages = [];
        $noTapakValues = [];
        $noSKKLValues = [];
        $noUKLValues = [];
        
        // Get activities from first row (header row) column D
        if (!empty($section['data']) && !empty($section['data'][0]['data_type'])) {
            $activities = $section['data'][0]['data_type']; // Column D data
        }
        
        // Get location from first row (header row) column C (alamat)
        // We need to get this from raw data since processDataRow doesn't capture column C
        if (!empty($section['data']) && !empty($section['data'][0]['raw_data']['C'])) {
            $location = trim($section['data'][0]['raw_data']['C']);
        }
        
        foreach ($section['data'] as $dataRow) {
            // Extract land area
            if ($dataRow['land_area'] > 0) {
                $landArea = $dataRow['land_area'];
            }
            
            // Extract BCR percentage - prioritize specific BCR from this section
            // Always use section-specific BCR if found, regardless of value
            if ($dataRow['bcr_percentage'] > 0 && $dataRow['bcr_percentage'] <= 100) {
                $bcrPercentage = $dataRow['bcr_percentage'];
            }
            
            // Extract additional location info from village/subdistrict data if main location is empty
            if (empty($location) && !empty($dataRow['location'])) {
                $villages[] = trim(str_replace('Desa ', '', $dataRow['location']));
            }
            
            // Collect no_tapak values
            if (!empty($dataRow['no_tapak']) && !in_array($dataRow['no_tapak'], $noTapakValues)) {
                $noTapakValues[] = $dataRow['no_tapak'];
            }
            
            // Collect no_skkl values
            if (!empty($dataRow['no_skkl']) && !in_array($dataRow['no_skkl'], $noSKKLValues)) {
                $noSKKLValues[] = $dataRow['no_skkl'];
            }
            
            // Collect no_ukl values
            if (!empty($dataRow['no_ukl']) && !in_array($dataRow['no_ukl'], $noUKLValues)) {
                $noUKLValues[] = $dataRow['no_ukl'];
            }
        }
        
        // Use first village as fallback location if main location is empty
        if (empty($location)) {
            $location = !empty($villages) ? $villages[0] : 'Unknown Location';
        }
        
        // Merge multiple values with | separator
        $noTapak = !empty($noTapakValues) ? implode('|', $noTapakValues) : null;
        $noSKKL = !empty($noSKKLValues) ? implode('|', $noSKKLValues) : null;
        $noUKL = !empty($noUKLValues) ? implode('|', $noUKLValues) : null;
        
        // Calculate area using BCR formula: land_area * (bcr_percentage / 100)
        $calculatedArea = $landArea > 0 && $bcrPercentage > 0 ? 
                         round($landArea * ($bcrPercentage / 100), 2) : 0;
        
        // Determine building_function and sub_building_function based on activities and applicant name
        $buildingFunction = 'Mixed Development'; // Default
        $subBuildingFunction = null;
        
        // Get applicant name for PT validation
        $applicantName = $section['applicant_name'] ?? '';
        $isCompany = (strpos($applicantName, 'PT ') === 0 || strpos($applicantName, 'PT.') === 0);
        
        // Activity-based classification (priority over PT validation for specific activities)
        if (!empty($activities)) {
            $activitiesLower = strtolower($activities);
            
            // 1. FUNGSI KEAGAMAAN
            if (strpos($activitiesLower, 'masjid') !== false ||
                strpos($activitiesLower, 'gereja') !== false ||
                strpos($activitiesLower, 'pura') !== false ||
                strpos($activitiesLower, 'vihara') !== false ||
                strpos($activitiesLower, 'klenteng') !== false ||
                strpos($activitiesLower, 'tempat ibadah') !== false ||
                strpos($activitiesLower, 'keagamaan') !== false ||
                strpos($activitiesLower, 'mushola') !== false) {
                
                $buildingFunction = 'Fungsi Keagamaan';
                $subBuildingFunction = 'Fungsi Keagamaan';
            }
            
            // 2. FUNGSI HUNIAN (PERUMAHAN) - PRIORITY HIGHER THAN PT VALIDATION
            elseif (strpos($activitiesLower, 'perumahan') !== false ||
                    strpos($activitiesLower, 'perumhan') !== false ||
                    strpos($activitiesLower, 'perum') !== false ||
                    strpos($activitiesLower, 'rumah') !== false ||
                    strpos($activitiesLower, 'hunian') !== false ||
                    strpos($activitiesLower, 'residence') !== false ||
                    strpos($activitiesLower, 'residential') !== false ||
                    strpos($activitiesLower, 'housing') !== false ||
                    strpos($activitiesLower, 'town') !== false) {
                
                $buildingFunction = 'Fungsi Hunian';
                
                // Determine housing type based on area and keywords
                if (strpos($activitiesLower, 'mbr') !== false ||
                    strpos($activitiesLower, 'masyarakat berpenghasilan rendah') !== false ||
                    strpos($activitiesLower, 'sederhana') !== false ||
                    ($landArea > 0 && $landArea < 2000)) { // Small area indicates MBR
                    
                    $subBuildingFunction = 'Rumah Tinggal Deret (MBR) dan Rumah Tinggal Tunggal (MBR)';
                }
                elseif ($landArea > 0 && $landArea < 100) {
                    $subBuildingFunction = 'Sederhana <100';
                }
                elseif ($landArea > 0 && $landArea > 100) {
                    $subBuildingFunction = 'Tidak Sederhana >100';
                }
                else {
                    $subBuildingFunction = 'Tidak Sederhana >100'; // Default for housing
                }
            }
            
            // 3. FUNGSI SOSIAL BUDAYA
            elseif (strpos($activitiesLower, 'sekolah') !== false ||
                    strpos($activitiesLower, 'rumah sakit') !== false ||
                    strpos($activitiesLower, 'puskesmas') !== false ||
                    strpos($activitiesLower, 'klinik') !== false ||
                    strpos($activitiesLower, 'universitas') !== false ||
                    strpos($activitiesLower, 'kampus') !== false ||
                    strpos($activitiesLower, 'pendidikan') !== false ||
                    strpos($activitiesLower, 'kesehatan') !== false ||
                    strpos($activitiesLower, 'sosial') !== false ||
                    strpos($activitiesLower, 'budaya') !== false ||
                    strpos($activitiesLower, 'museum') !== false ||
                    strpos($activitiesLower, 'tower') !== false ||
                    strpos($activitiesLower, 'perpustakaan') !== false) {
                
                $buildingFunction = 'Fungsi Sosial Budaya';
                $subBuildingFunction = 'Fungsi Sosial Budaya';
            }
            
            // 4. FUNGSI USAHA
            elseif (strpos($activitiesLower, 'perdagangan') !== false ||
                    strpos($activitiesLower, 'dagang') !== false ||
                    strpos($activitiesLower, 'toko') !== false ||
                    strpos($activitiesLower, 'usaha') !== false ||
                    strpos($activitiesLower, 'komersial') !== false ||
                    strpos($activitiesLower, 'pabrik') !== false ||
                    strpos($activitiesLower, 'industri') !== false ||
                    strpos($activitiesLower, 'manufaktur') !== false ||
                    strpos($activitiesLower, 'bisnis') !== false ||
                    strpos($activitiesLower, 'resto') !== false ||
                    strpos($activitiesLower, 'villa') !== false ||
                    strpos($activitiesLower, 'vila') !== false ||
                    strpos($activitiesLower, 'gudang') !== false ||
                    strpos($activitiesLower, 'pergudangan') !== false ||
                    strpos($activitiesLower, 'kolam renang') !== false ||
                    strpos($activitiesLower, 'minimarket') !== false ||
                    strpos($activitiesLower, 'supermarket') !== false ||
                    strpos($activitiesLower, 'perdaganagan') !== false ||
                    strpos($activitiesLower, 'waterpark') !== false ||
                    strpos($activitiesLower, 'pasar') !== false ||
                    strpos($activitiesLower, 'kantor') !== false) {
                
                $buildingFunction = 'Fungsi Usaha';
                
                // Determine business size based on land area for non-PT businesses
                if ($landArea > 0 && $landArea > 500) { // > 500 m² considered large business
                    $subBuildingFunction = 'Usaha Besar (Non-Mikro)';
                } else {
                    $subBuildingFunction = 'UMKM'; // For small individual businesses
                }
            }
            
            // 5. FUNGSI CAMPURAN
            elseif (strpos($activitiesLower, 'campuran') !== false ||
                    strpos($activitiesLower, 'mixed') !== false ||
                    strpos($activitiesLower, 'mix') !== false ||
                    strpos($activitiesLower, 'multi') !== false) {
                
                $buildingFunction = 'Fungsi Campuran (lebih dari 1)';
                
                // Determine mixed use size
                if ($landArea > 0 && $landArea > 3000) { // > 3000 m² considered large mixed use
                    $subBuildingFunction = 'Campuran Besar';
                } else {
                    $subBuildingFunction = 'Campuran Kecil';
                }
            }
            // If no specific activity detected, fall back to PT validation
            else {
                // PT Company validation - PT/PT. automatically classified as Fungsi Usaha
                if ($isCompany) {
                    $buildingFunction = 'Fungsi Usaha';
                    
                    // For PT companies: area-based classification
                    if ($landArea > 0 && $landArea < 500) { // < 500 m² for PT = Non-Mikro (since PT is already established business)
                        $subBuildingFunction = 'Usaha Besar (Non-Mikro)';
                    } elseif ($landArea >= 500) { // >= 500 m² for PT = Large Business
                        $subBuildingFunction = 'Usaha Besar (Non-Mikro)';
                    } else {
                        $subBuildingFunction = 'Usaha Besar (Non-Mikro)'; // Default for PT
                    }
                }
            }
        }
        // If no activities, fall back to PT validation
        else {
            // PT Company validation - PT/PT. automatically classified as Fungsi Usaha
            if ($isCompany) {
                $buildingFunction = 'Fungsi Usaha';
                
                // For PT companies: area-based classification
                if ($landArea > 0 && $landArea < 500) { // < 500 m² for PT = Non-Mikro (since PT is already established business)
                    $subBuildingFunction = 'Usaha Besar (Non-Mikro)';
                } elseif ($landArea >= 500) { // >= 500 m² for PT = Large Business
                    $subBuildingFunction = 'Usaha Besar (Non-Mikro)';
                } else {
                    $subBuildingFunction = 'Usaha Besar (Non-Mikro)'; // Default for PT
                }
            }
        }
        
        return [
            'land_area' => $landArea,
            'bcr_percentage' => $bcrPercentage,
            'calculated_area' => $calculatedArea,
            'location' => $location,
            'activities' => $activities, // Activities from column D of first row
            'building_function' => $buildingFunction,
            'sub_building_function' => $subBuildingFunction,
            'no_tapak' => $noTapak,
            'no_skkl' => $noSKKL,
            'no_ukl' => $noUKL,
            'has_valid_data' => $landArea > 0 // Flag untuk validasi
        ];
    }

    /**
     * Clean and convert string to numeric value
     */
    private function cleanNumericValue($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remove non-numeric characters except decimal points and commas
        $cleaned = preg_replace('/[^0-9.,]/', '', $value);
        
        // Handle different decimal separators
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Both comma and dot present, assume comma is thousands separator
            $cleaned = str_replace(',', '', $cleaned);
        } elseif (strpos($cleaned, ',') !== false) {
            // Only comma present, assume it's decimal separator
            $cleaned = str_replace(',', '.', $cleaned);
        }
        
        return is_numeric($cleaned) ? (float) $cleaned : 0;
    }
}
