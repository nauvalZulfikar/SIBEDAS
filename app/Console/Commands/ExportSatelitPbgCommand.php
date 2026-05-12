<?php

namespace App\Console\Commands;

use App\Exports\SatelitPbgSummaryExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Export hasil merge Satelit ↔ PBG ke 2 file:
 *   - summary.xlsx (31 rows, multi-sheet stats per kec)
 *   - detail.csv   (semua / sebagian bangunan, streaming)
 *
 * Pakai CSV utk detail karena 590k baris OOM di PhpSpreadsheet.
 * CSV opens langsung di Excel.
 */
class ExportSatelitPbgCommand extends Command
{
    protected $signature = 'satelit-pbg:export
        {--min-area=0 : Min luas bangunan m² (0 = semua)}
        {--scope=tidak-berizin : tidak-berizin | berizin | semua}
        {--out= : Output dir (default: storage/app/private/exports/satelit-pbg/<timestamp>/)}';

    protected $description = 'Export hasil merge Satelit ↔ PBG → summary.xlsx + detail.csv';

    private const KECS = [
        'Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung',
        'Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot',
        'Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu',
        'Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali',
        'Rancaekek','Soreang','Solokanjeruk',
    ];

    public function handle(): int
    {
        $minArea = (int) $this->option('min-area');
        $scope   = $this->option('scope');
        if (!in_array($scope, ['tidak-berizin', 'berizin', 'semua'], true)) {
            $this->error("--scope harus salah satu: tidak-berizin, berizin, semua");
            return self::FAILURE;
        }

        $outDir = $this->option('out');
        if (!$outDir) {
            $stamp = now()->format('Y-m-d_His');
            $area  = $minArea > 0 ? "_min{$minArea}m2" : '';
            $rel   = "exports/satelit-pbg/{$scope}{$area}_{$stamp}";
            Storage::disk('local')->makeDirectory($rel);
            $outDir = Storage::disk('local')->path($rel);
        } else {
            if (!is_dir($outDir)) mkdir($outDir, 0755, true);
        }

        $this->info("Generating to: {$outDir}");
        $this->line("  scope    = {$scope}");
        $this->line("  min-area = {$minArea} m²");

        // 1) summary.xlsx
        $this->line('1/2 summary.xlsx ...');
        $start = microtime(true);
        $xlsxPath = $outDir . DIRECTORY_SEPARATOR . 'summary.xlsx';
        Excel::store(
            new SatelitPbgSummaryExport($minArea),
            str_replace(Storage::disk('local')->path(''), '', $xlsxPath),
            'local'
        );
        $this->line(sprintf('   ✓ %.1fs · %d KB', microtime(true)-$start, filesize($xlsxPath)/1024));

        // 2) detail.csv (streaming via cursor)
        $this->line('2/2 detail.csv ...');
        $start = microtime(true);
        $csvPath = $outDir . DIRECTORY_SEPARATOR . 'detail.csv';
        $rows = $this->writeDetailCsv($csvPath, $minArea, $scope);
        $this->line(sprintf('   ✓ %.1fs · %s rows · %.1f MB',
            microtime(true)-$start,
            number_format($rows),
            filesize($csvPath)/1024/1024
        ));

        $this->newLine();
        $this->info('Selesai. Buka kedua file di Excel:');
        $this->line('  ' . $xlsxPath);
        $this->line('  ' . $csvPath);

        return self::SUCCESS;
    }

    private function writeDetailCsv(string $path, int $minArea, string $scope): int
    {
        $fp = fopen($path, 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel ID)

        fputcsv($fp, [
            'ID Bangunan','Kecamatan','Kelurahan','Latitude','Longitude',
            'Luas (m2)','Confidence','Sumber Deteksi',
            'PBG Status','PBG Task ID','Match Distance (m)',
        ]);

        $q = DB::table('detected_buildings as db')
            ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
            ->whereIn('db.kecamatan', self::KECS)
            ->select([
                'db.id','db.kecamatan',
                'db.building_ward_name as kelurahan',
                'db.latitude','db.longitude',
                'db.estimated_area_m2 as area',
                'db.confidence_score as conf',
                'db.detection_source',
                'db.matched_pbg_task_id as pbg_id',
                'db.match_distance_m as dist',
                'pt.status as pbg_status',
                'pt.id as pt_id',
            ]);
        if ($minArea > 0) $q->where('db.estimated_area_m2', '>=', $minArea);
        if ($scope === 'tidak-berizin') {
            $q->where(function ($w) {
                $w->whereNull('db.matched_pbg_task_id')
                  ->orWhereNull('pt.id')
                  ->orWhereIn('pt.status', [3, 9, 22]);
            });
        } elseif ($scope === 'berizin') {
            $q->where('pt.status', 20);
        }

        $count = 0;
        foreach ($q->orderBy('db.id')->cursor() as $r) {
            $matchedId = $r->pbg_id;
            $ptId = $r->pt_id;
            $code = $r->pbg_status;

            if ($matchedId === null) {
                $label = 'Tanpa Match (Unmatched)';
            } elseif ($ptId === null) {
                $label = 'Orphan FK (PBG dihapus)';
            } else {
                $label = match ((int) $code) {
                    20      => 'SK Terbit',
                    3, 9    => 'Ditolak',
                    22      => 'Batal',
                    default => 'Proses (status=' . $code . ')',
                };
            }

            fputcsv($fp, [
                $r->id, $r->kecamatan, $r->kelurahan ?? '',
                $r->latitude, $r->longitude,
                $r->area, $r->conf, $r->detection_source,
                $label, $matchedId, $r->dist,
            ]);
            $count++;
        }
        fclose($fp);
        return $count;
    }
}
