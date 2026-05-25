<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Tier-2 weekly maintenance: pre-warm Martin tile cache for the
 * Kabupaten Bandung viewport (z14-z16) and pre-generate KRK PDFs for
 * every kecamatan so Monday-morning users see instant maps.
 *
 *   php artisan sibedas:weekly
 *
 * Both steps are idempotent: tiles regenerate from postgis (cache is
 * authoritative downstream), PDFs overwrite tmp/krk-cache/<kec>.pdf.
 */
class WeeklyWarmup extends Command
{
    protected $signature = 'sibedas:weekly {--skip-tiles} {--skip-pdf} {--zoom-min=14} {--zoom-max=16}';
    protected $description = 'Tier-2 weekly: pre-warm tile cache + pre-generate KRK PDFs for all 31 kecamatan';

    private const KECAMATAN = [
        'Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung',
        'Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot',
        'Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu',
        'Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali',
        'Rancaekek','Soreang','Solokanjeruk',
    ];

    // Kab. Bandung bounding box. We tile-walk this rectangle.
    private const BBOX = [
        'south' => -7.310, 'west' => 107.250,
        'north' => -6.810, 'east' => 107.940,
    ];

    public function handle(): int
    {
        $t0 = microtime(true);
        $this->info("[weekly] Starting Tier-2 warmup @ " . now()->toIso8601String());

        if (!$this->option('skip-tiles')) {
            $this->warmTiles((int) $this->option('zoom-min'), (int) $this->option('zoom-max'));
        }
        if (!$this->option('skip-pdf')) {
            $this->preGeneratePdfs();
        }

        $secs = round(microtime(true) - $t0, 1);
        $this->info("[weekly] Done in {$secs}s");
        return 0;
    }

    private function warmTiles(int $zMin, int $zMax): void
    {
        $this->info("[tiles] Pre-warming z={$zMin}..z={$zMax} for Kab. Bandung viewport…");
        $tilesHit = 0;
        $tilesTotal = 0;
        $start = microtime(true);
        for ($z = $zMin; $z <= $zMax; $z++) {
            [$x0, $y0] = $this->lonLatToTile(self::BBOX['west'], self::BBOX['north'], $z);
            [$x1, $y1] = $this->lonLatToTile(self::BBOX['east'], self::BBOX['south'], $z);
            for ($x = $x0; $x <= $x1; $x++) {
                for ($y = $y0; $y <= $y1; $y++) {
                    $tilesTotal++;
                    // Hit Martin directly on loopback — bypass Laravel proxy for speed.
                    try {
                        $r = Http::timeout(8)->get("http://127.0.0.1:3000/building_tile/{$z}/{$x}/{$y}", [
                            'source' => 'microsoft_footprints',
                        ]);
                        if ($r->successful() && strlen($r->body()) > 0) {
                            $tilesHit++;
                        }
                    } catch (\Throwable $e) { /* swallow — log later if many */ }
                    if ($tilesTotal % 500 === 0) {
                        $elapsed = round(microtime(true) - $start, 1);
                        $this->info("  …{$tilesTotal} tiles in {$elapsed}s (hits={$tilesHit})");
                    }
                }
            }
        }
        $elapsed = round(microtime(true) - $start, 1);
        $this->info("[tiles] {$tilesTotal} tiles touched in {$elapsed}s (non-empty: {$tilesHit})");
    }

    private function preGeneratePdfs(): void
    {
        $this->info('[pdf] Pre-generating KRK PDFs for ' . count(self::KECAMATAN) . ' kecamatan…');
        $cacheDir = base_path('tmp/krk-cache');
        if (!is_dir($cacheDir)) { mkdir($cacheDir, 0775, true); }

        $node = (new ExecutableFinder())->find('node') ?? 'C:\\Program Files\\nodejs\\node.exe';
        $script = base_path('tmp/krk-to-pdf.mjs');

        $env = array_filter([
            'SystemRoot'   => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR'       => getenv('WINDIR') ?: 'C:\\Windows',
            'APPDATA'      => getenv('APPDATA'),
            'LOCALAPPDATA' => getenv('LOCALAPPDATA'),
            'USERPROFILE'  => getenv('USERPROFILE'),
            'TEMP'         => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP'          => getenv('TMP') ?: sys_get_temp_dir(),
            'PATH'         => getenv('PATH'),
            'PLAYWRIGHT_BROWSERS_PATH' => getenv('PLAYWRIGHT_BROWSERS_PATH') ?: 'D:\\dev-cache\\ms-playwright',
        ], fn ($v) => $v !== false && $v !== null && $v !== '');

        $ok = 0;
        $fail = 0;
        foreach (self::KECAMATAN as $kec) {
            $start = microtime(true);
            $htmlPath = base_path("tmp/krk-cache/_{$kec}.html");
            $pdfPath  = "{$cacheDir}/{$kec}.pdf";

            // Render via the controller, write html, then call node renderer.
            try {
                $ctl = app(\App\Http\Controllers\Dashboards\KrkPrintController::class);
                $view = $ctl->show(request(), $kec);
                file_put_contents($htmlPath, $view->render());

                $proc = new Process([$node, $script, $kec, $pdfPath, $htmlPath], base_path(), $env, null, 120);
                $proc->run();
                @unlink($htmlPath);

                if ($proc->isSuccessful() && file_exists($pdfPath)) {
                    $size = round(filesize($pdfPath) / 1024, 1);
                    $secs = round(microtime(true) - $start, 1);
                    $this->info("  ✓ {$kec}.pdf  {$size}KB  {$secs}s");
                    $ok++;
                } else {
                    $this->error("  ✗ {$kec} failed: " . substr($proc->getErrorOutput(), 0, 200));
                    $fail++;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ {$kec}: " . $e->getMessage());
                $fail++;
            }
        }
        $this->info("[pdf] {$ok} succeeded, {$fail} failed; cached in tmp/krk-cache/");
    }

    /** OSM-style tile XYZ from lon/lat. */
    private function lonLatToTile(float $lon, float $lat, int $z): array
    {
        $n = 1 << $z;
        $x = (int) floor(($lon + 180) / 360 * $n);
        $latRad = deg2rad($lat);
        $y = (int) floor((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n);
        return [$x, $y];
    }
}
