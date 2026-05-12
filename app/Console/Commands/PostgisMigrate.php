<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent runner for PostGIS schema migrations.
 *
 * Reads .sql files from database/migrations/postgis/ in lexical order and
 * applies each one that hasn't been recorded yet. State is tracked in a
 * postgis_migrations table on the PostGIS connection (NOT on MySQL).
 *
 * Each .sql file is expected to be itself idempotent (uses IF NOT EXISTS)
 * so re-running is safe even if the tracking row is missing.
 */
class PostgisMigrate extends Command
{
    protected $signature = 'postgis:migrate
                            {--fresh : Drop the tracking table first (DEV ONLY — does NOT drop data tables)}
                            {--pretend : Print which files would run without executing}';

    protected $description = 'Apply pending PostGIS .sql migrations from database/migrations/postgis/';

    private const MIGRATIONS_DIR = 'database/migrations/postgis';
    private const TRACKING_TABLE = 'postgis_migrations';

    public function handle(): int
    {
        $conn = DB::connection('postgis');

        if ($this->option('fresh')) {
            $conn->statement('DROP TABLE IF EXISTS ' . self::TRACKING_TABLE);
            $this->warn('Dropped tracking table — all migrations will re-apply (data tables untouched).');
        }

        $this->ensureTrackingTable($conn);

        $files = $this->discoverMigrationFiles();
        if (empty($files)) {
            $this->info('No migration files found in ' . self::MIGRATIONS_DIR);
            return self::SUCCESS;
        }

        $applied = $conn->table(self::TRACKING_TABLE)->pluck('filename')->all();
        $pending = array_values(array_filter($files, fn ($f) => !in_array(basename($f), $applied, true)));

        if (empty($pending)) {
            $this->info('Nothing to migrate. (' . count($applied) . ' already applied)');
            return self::SUCCESS;
        }

        $this->info('Pending: ' . count($pending) . ' file(s)');
        foreach ($pending as $path) {
            $name = basename($path);
            if ($this->option('pretend')) {
                $this->line("  [pretend] would apply: {$name}");
                continue;
            }
            $this->line("  applying: {$name}");
            $sql = file_get_contents($path);
            $conn->unprepared($sql);
            $conn->table(self::TRACKING_TABLE)->insert([
                'filename'   => $name,
                'applied_at' => now(),
            ]);
            $this->info("    ok");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function ensureTrackingTable($conn): void
    {
        $conn->unprepared(
            'CREATE TABLE IF NOT EXISTS ' . self::TRACKING_TABLE . ' (
                filename   VARCHAR(255) PRIMARY KEY,
                applied_at TIMESTAMPTZ NOT NULL
            )'
        );
    }

    private function discoverMigrationFiles(): array
    {
        $dir = base_path(self::MIGRATIONS_DIR);
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files, SORT_NATURAL);
        return $files;
    }
}
