<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TruncateSpatialPlanningData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spatial-planning:truncate 
                            {--force : Force truncate without confirmation}
                            {--backup : Create backup before truncate}
                            {--dry-run : Show what would be truncated without actually doing it}
                            {--only-active : Only truncate active calculations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate spatial planning data with related calculable retributions and retribution calculations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $force = $this->option('force');
            $backup = $this->option('backup');
            $dryRun = $this->option('dry-run');
            $onlyActive = $this->option('only-active');

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No data will be truncated');
            }

            // Check existing data
            $this->showDataStatistics();

            // Confirm truncation if not in force mode
            if (!$force && !$dryRun) {
                if (!$this->confirm('This will permanently delete all spatial planning data and related calculations. Continue?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }

            // Create backup if requested
            if ($backup && !$dryRun) {
                $this->createBackup();
            }

            // Perform truncation
            if ($dryRun) {
                $this->performDryRun();
            } else {
                $this->performTruncation($onlyActive);
            }

            // Show final statistics
            if (!$dryRun) {
                $this->showDataStatistics('AFTER');
            }

            return 0;
        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("TruncateSpatialPlanningData failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Show data statistics
     */
    private function showDataStatistics(string $prefix = 'BEFORE'): void
    {
        $this->info("=== {$prefix} TRUNCATION ===");
        
        $spatialCount = DB::table('spatial_plannings')->count();
        $calculableCount = DB::table('calculable_retributions')->count();
        $activeCalculableCount = DB::table('calculable_retributions')->where('is_active', true)->count();
        $calculationCount = DB::table('retribution_calculations')->count();
        
        $this->table(
            ['Table', 'Total Records', 'Active Records'],
            [
                ['spatial_plannings', $spatialCount, '-'],
                ['calculable_retributions', $calculableCount, $activeCalculableCount],
                ['retribution_calculations', $calculationCount, '-'],
            ]
        );

        // Show breakdown by building function
        $buildingFunctionStats = DB::table('spatial_plannings')
            ->select('building_function', DB::raw('count(*) as total'))
            ->groupBy('building_function')
            ->get();

        if ($buildingFunctionStats->isNotEmpty()) {
            $this->info('Building Function Breakdown:');
            foreach ($buildingFunctionStats as $stat) {
                $this->line("  - {$stat->building_function}: {$stat->total} records");
            }
        }

        $this->newLine();
    }

    /**
     * Create backup of data
     */
    private function createBackup(): void
    {
        $this->info('Creating backup...');
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = storage_path("backups/spatial_planning_backup_{$timestamp}");
        
        // Create backup directory
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Backup spatial plannings
        $spatialData = DB::table('spatial_plannings')->get();
        file_put_contents(
            "{$backupPath}/spatial_plannings.json",
            $spatialData->toJson(JSON_PRETTY_PRINT)
        );

        // Backup calculable retributions
        $calculableData = DB::table('calculable_retributions')->get();
        file_put_contents(
            "{$backupPath}/calculable_retributions.json",
            $calculableData->toJson(JSON_PRETTY_PRINT)
        );

        // Backup retribution calculations
        $calculationData = DB::table('retribution_calculations')->get();
        file_put_contents(
            "{$backupPath}/retribution_calculations.json",
            $calculationData->toJson(JSON_PRETTY_PRINT)
        );

        $this->info("Backup created at: {$backupPath}");
    }

    /**
     * Perform dry run
     */
    private function performDryRun(): void
    {
        $this->info('DRY RUN - Would truncate the following:');
        
        $spatialCount = DB::table('spatial_plannings')->count();
        $calculableCount = DB::table('calculable_retributions')->count();
        $calculationCount = DB::table('retribution_calculations')->count();
        
        $this->line("  - spatial_plannings: {$spatialCount} records");
        $this->line("  - calculable_retributions: {$calculableCount} records");
        $this->line("  - retribution_calculations: {$calculationCount} records");
        
        $this->warn('No actual data was truncated (dry run mode)');
    }

    /**
     * Perform actual truncation
     */
    private function performTruncation(bool $onlyActive = false): void
    {
        $this->info('Starting truncation...');
        
        try {
            // Disable foreign key checks for safe truncation
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            
            if ($onlyActive) {
                // Only truncate active calculations
                $this->truncateActiveOnly();
            } else {
                // Truncate all data
                $this->truncateAllData();
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            
            $this->info('✅ Truncation completed successfully!');
            
        } catch (Exception $e) {
            // Make sure to re-enable foreign key checks even on error
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Exception $fkError) {
                $this->error('Failed to re-enable foreign key checks: ' . $fkError->getMessage());
            }
            
            throw $e;
        }
    }

    /**
     * Truncate only active calculations
     */
    private function truncateActiveOnly(): void
    {
        $this->info('Truncating only active calculations...');
        
        // Delete active calculable retributions
        $deletedActive = DB::table('calculable_retributions')
            ->where('is_active', true)
            ->delete();
        $this->info("Deleted {$deletedActive} active calculable retributions");
        
        // Delete orphaned retribution calculations
        $deletedOrphaned = DB::table('retribution_calculations')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('calculable_retributions')
                    ->whereColumn('calculable_retributions.retribution_calculation_id', 'retribution_calculations.id');
            })
            ->delete();
        $this->info("Deleted {$deletedOrphaned} orphaned retribution calculations");
        
        // Keep spatial plannings but remove their calculation relationships
        $this->info('Spatial plannings data preserved (only calculations removed)');
    }

    /**
     * Truncate all data
     */
    private function truncateAllData(): void
    {
        $this->info('Truncating all data...');
        
        // Get counts before truncation
        $spatialCount = DB::table('spatial_plannings')->count();
        $calculableCount = DB::table('calculable_retributions')->count();
        $calculationCount = DB::table('retribution_calculations')->count();
        
        // Truncate tables in correct order
        DB::table('calculable_retributions')->truncate();
        $this->info("Truncated calculable_retributions table ({$calculableCount} records)");
        
        DB::table('retribution_calculations')->truncate();
        $this->info("Truncated retribution_calculations table ({$calculationCount} records)");
        
        DB::table('spatial_plannings')->truncate();
        $this->info("Truncated spatial_plannings table ({$spatialCount} records)");
        
        // Reset auto increment
        DB::statement('ALTER TABLE calculable_retributions AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE retribution_calculations AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE spatial_plannings AUTO_INCREMENT = 1');
        $this->info('Reset auto increment counters');
    }
} 