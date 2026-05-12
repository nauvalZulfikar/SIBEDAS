<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportPbbCommand extends Command
{
    protected $signature = 'pbb:import
                            {path : Path to data-pbb.xlsx or pbb_full_decoded.csv}
                            {--truncate : Truncate pbb_records before import}
                            {--chunk=2000 : Bulk insert chunk size (max ~4300 with 15 cols due to MySQL placeholder limit)}
                            {--no-lookup : Skip rebuilding lookup tables}';

    protected $description = 'Import PBB records from Excel/CSV into pbb_records';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            if (!$this->confirm('Truncate pbb_records, pbb_kecamatan_lookup, pbb_kelurahan_lookup?', false)) {
                return self::FAILURE;
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('pbb_records')->truncate();
            DB::table('pbb_kecamatan_lookup')->truncate();
            DB::table('pbb_kelurahan_lookup')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn('Tables truncated.');
        }

        $start = microtime(true);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        try {
            $count = match ($ext) {
                'csv' => $this->importFromCsv($path),
                'xlsx' => $this->importFromXlsx($path),
                default => throw new \RuntimeException("Unsupported format: .{$ext}"),
            };
        } catch (\Throwable $e) {
            $this->error('Import failed: ' . $e->getMessage());
            Log::error('pbb:import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $start, 1);
        $this->info("Inserted {$count} rows in {$elapsed}s");

        if (!$this->option('no-lookup')) {
            $this->buildLookupTables();
        }

        return self::SUCCESS;
    }

    private function importFromCsv(string $path): int
    {
        $f = fopen($path, 'r');
        if ($f === false) {
            throw new \RuntimeException("Cannot open {$path}");
        }
        fgetcsv($f);

        $chunkSize = (int) $this->option('chunk');
        $count = 0;
        $skipped = 0;
        $buffer = [];
        $lastReport = microtime(true);
        $now = now();

        while (($row = fgetcsv($f)) !== false) {
            if (count($row) < 9 || empty($row[0])) {
                $skipped++;
                continue;
            }
            $parsed = $this->parseRow($row, $now);
            if ($parsed === null) {
                $skipped++;
                continue;
            }
            $buffer[] = $parsed;

            if (count($buffer) >= $chunkSize) {
                $count += $this->flushBuffer($buffer);
                $buffer = [];
                if (microtime(true) - $lastReport > 5) {
                    $this->line("  ...{$count} rows imported");
                    $lastReport = microtime(true);
                }
            }
        }

        if (!empty($buffer)) {
            $count += $this->flushBuffer($buffer);
        }
        fclose($f);

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} malformed rows");
        }
        return $count;
    }

    private function importFromXlsx(string $path): int
    {
        $this->warn('XLSX direct import is slow (~10-20 min). Recommended: extract to CSV first.');

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $infos = $reader->listWorksheetInfo($path);
        $count = 0;
        $now = now();
        $rowsPerChunk = 10000;
        $bufferSize = (int) $this->option('chunk');

        foreach ($infos as $info) {
            $sheetName = $info['worksheetName'];
            $totalRows = $info['totalRows'];
            $this->info("Sheet '{$sheetName}': {$totalRows} rows");

            $startRow = 2;
            while ($startRow <= $totalRows) {
                $endRow = min($startRow + $rowsPerChunk - 1, $totalRows);
                $filter = new class($startRow, $endRow) implements IReadFilter {
                    public function __construct(private int $start, private int $end) {}
                    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                    {
                        return $row >= $this->start && $row <= $this->end;
                    }
                };
                $reader->setReadFilter($filter);
                $reader->setLoadSheetsOnly([$sheetName]);
                $spreadsheet = $reader->load($path);
                $sheet = $spreadsheet->getSheetByName($sheetName);

                $buffer = [];
                foreach ($sheet->getRowIterator($startRow, $endRow) as $row) {
                    $cells = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $cells[] = $cell->getValue();
                    }
                    if (empty($cells[0])) {
                        continue;
                    }
                    while (count($cells) < 9) $cells[] = null;
                    $cells[] = $sheetName;
                    $parsed = $this->parseRow($cells, $now);
                    if ($parsed === null) continue;
                    $buffer[] = $parsed;
                    if (count($buffer) >= $bufferSize) {
                        $count += $this->flushBuffer($buffer);
                        $buffer = [];
                    }
                }
                if (!empty($buffer)) {
                    $count += $this->flushBuffer($buffer);
                }
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                $this->line("  '{$sheetName}': rows {$startRow}-{$endRow} | total {$count}");
                $startRow = $endRow + 1;
            }
        }
        return $count;
    }

    private function parseRow(array $row, Carbon $now): ?array
    {
        $nop = trim((string) $row[0]);
        if (!preg_match('/^32\.06\.\d{3}\.\d{3}\.\d{3}\.\d{4}\.\d$/', $nop)) {
            return null;
        }
        $parts = explode('.', $nop);

        return [
            'nop' => $nop,
            'nama_wp' => $row[1] !== null ? mb_substr((string) $row[1], 0, 255) : null,
            'alamat' => $row[2] !== null ? mb_substr((string) $row[2], 0, 500) : null,
            'terbangun_flag' => $row[3] !== null && $row[3] !== '' ? mb_substr((string) $row[3], 0, 64) : null,
            'nama_bangunan' => $row[4] !== null && $row[4] !== '' ? mb_substr((string) $row[4], 0, 128) : null,
            'luas_bumi' => max(0, (int) $row[5]),
            'luas_bangunan' => max(0, (int) $row[6]),
            'kecamatan_djp_code' => $parts[2],
            'desa_djp_code' => $parts[3],
            'kecamatan_name' => mb_substr(strtoupper(trim((string) ($row[7] ?? ''))), 0, 64),
            'kelurahan_name' => mb_substr(strtoupper(trim((string) ($row[8] ?? ''))), 0, 64),
            'source_sheet' => isset($row[9]) ? mb_substr((string) $row[9], 0, 16) : null,
            'imported_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function flushBuffer(array $buffer): int
    {
        DB::table('pbb_records')->insertOrIgnore($buffer);
        return count($buffer);
    }

    private function buildLookupTables(): void
    {
        $this->info('Rebuilding lookup tables...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('pbb_kecamatan_lookup')->truncate();
        DB::table('pbb_kelurahan_lookup')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::statement("
            INSERT INTO pbb_kecamatan_lookup
                (djp_code, kecamatan_name, nop_count, terbangun_count,
                 sum_luas_bumi_m2, sum_luas_bangunan_m2, kelurahan_count,
                 last_synced_at, created_at, updated_at)
            SELECT
                kecamatan_djp_code,
                MAX(kecamatan_name),
                COUNT(*),
                SUM(CASE WHEN luas_bangunan > 0 THEN 1 ELSE 0 END),
                SUM(luas_bumi),
                SUM(luas_bangunan),
                COUNT(DISTINCT desa_djp_code),
                NOW(), NOW(), NOW()
            FROM pbb_records
            GROUP BY kecamatan_djp_code
        ");

        DB::statement("
            INSERT INTO pbb_kelurahan_lookup
                (djp_kec_code, djp_desa_code, kelurahan_name,
                 nop_count, terbangun_count, sum_luas_bangunan_m2,
                 last_synced_at, created_at, updated_at)
            SELECT
                kecamatan_djp_code,
                desa_djp_code,
                MAX(kelurahan_name),
                COUNT(*),
                SUM(CASE WHEN luas_bangunan > 0 THEN 1 ELSE 0 END),
                SUM(luas_bangunan),
                NOW(), NOW(), NOW()
            FROM pbb_records
            GROUP BY kecamatan_djp_code, desa_djp_code
        ");

        $kec = DB::table('pbb_kecamatan_lookup')->count();
        $kel = DB::table('pbb_kelurahan_lookup')->count();
        $this->info("Lookup built: {$kec} kec, {$kel} kelurahan");
    }
}
