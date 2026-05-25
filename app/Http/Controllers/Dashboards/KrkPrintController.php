<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KrkPrintController extends Controller
{
    /**
     * Render an A4-portrait HTML view of the Lampiran KRK for the given
     * kecamatan. This view is consumed by a headless browser (Playwright)
     * which screenshots it as PDF — never rendered to an actual user.
     */
    private function renderPrintHtml(string $kecamatan): string
    {
        // Re-use show() logic to produce the rendered HTML string.
        $view = $this->show(request(), $kecamatan);
        return $view->render();
    }

    public function show(Request $request, string $kecamatan)
    {
        $kecRow = DB::connection('mysql')->table('kecamatan_stats')
            ->where('kecamatan', $kecamatan)
            ->where('min_area_bucket', 0)
            ->first();

        $totalDetected = (int) ($kecRow->total_detected ?? 0);
        $withoutPermit = (int) ($kecRow->without_permit_total ?? 0);
        $permitValid = (int) ($kecRow->permit_valid_count ?? 0);
        $permitInProcess = (int) ($kecRow->permit_in_process_count ?? 0);

        // Pull zone breakdown for THIS kecamatan from postgis via the
        // sibedas_spatial connection. If the connection isn't configured we
        // fall back to empty so the template still renders.
        $zoneRows = [];
        try {
            $zoneRows = DB::connection('postgis')->select('
                SELECT zone_category, COUNT(*) AS jumlah,
                       ROUND(SUM(area_m2)::numeric / 10000.0, 1) AS total_ha
                FROM rtrw_pola_ruang
                WHERE district = ?
                GROUP BY zone_category
                ORDER BY total_ha DESC
            ', [$kecamatan]);
        } catch (\Throwable $e) {
            \Log::warning('[KrkPrint] postgis query failed: ' . $e->getMessage());
        }

        // Inline the kecamatan geojson too — file:// load can't fetch other
        // file:// resources, so we embed everything the page needs.
        $kecGeojson = null;
        $kecPath = public_path('data/kecamatan_kab_bandung.geojson');
        if (file_exists($kecPath)) {
            $kecGeojson = json_decode(file_get_contents($kecPath), true);
        }

        // Pull RTRW polygons for THIS kecamatan as GeoJSON — small enough
        // (a few hundred per kec) to embed inline in the page, avoiding the
        // CORS/vector-tile layer-name fuss.
        $rtrwGeojson = ['type' => 'FeatureCollection', 'features' => []];
        try {
            $rows = DB::connection('postgis')->select('
                SELECT id, zone_category, color_hex,
                       ST_AsGeoJSON(geom) AS geom
                FROM rtrw_pola_ruang
                WHERE district = ?
                LIMIT 5000
            ', [$kecamatan]);
            foreach ($rows as $r) {
                $rtrwGeojson['features'][] = [
                    'type'       => 'Feature',
                    'properties' => ['zone_category' => $r->zone_category, 'color_hex' => $r->color_hex],
                    'geometry'   => json_decode($r->geom, true),
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('[KrkPrint] geojson query failed: ' . $e->getMessage());
        }

        return view('dashboards.krk-print', [
            'kecamatan'        => $kecamatan,
            'generatedAt'      => now()->format('d M Y H:i'),
            'totalDetected'    => $totalDetected,
            'withoutPermit'    => $withoutPermit,
            'permitValid'      => $permitValid,
            'permitInProcess'  => $permitInProcess,
            'zoneRows'         => $zoneRows,
            'rtrwGeojson'      => $rtrwGeojson,
            'kecGeojson'       => $kecGeojson,
        ]);
    }

    /**
     * Trigger headless Chrome to render the print view as A4 PDF, then
     * stream the result back to the user as a download.
     */
    public function export(Request $request, string $kecamatan): BinaryFileResponse
    {
        if (!preg_match('/^[A-Za-z ]+$/', $kecamatan)) {
            abort(400, 'Invalid kecamatan name');
        }

        // If the weekly warmup left a fresh cached PDF (≤7 days old),
        // stream that instead of regenerating on the hot path.
        $cachedPath = base_path("tmp/krk-cache/{$kecamatan}.pdf");
        if (file_exists($cachedPath) && (time() - filemtime($cachedPath)) < 7 * 86400) {
            return response()->download($cachedPath, "Lampiran_KRK_{$kecamatan}.pdf", [
                'Content-Type' => 'application/pdf',
                'X-Krk-Cache'  => 'HIT',
            ]);
        }

        $stamp = now()->format('Ymd-His');
        $outPath = base_path("tmp/krk-{$kecamatan}-{$stamp}.pdf");
        $script  = base_path('tmp/krk-to-pdf.mjs');

        // Render the print view to a temp HTML file so the headless
        // browser can load it via file:// — avoids the artisan-serve
        // single-threaded deadlock (Windows can't fork PHP workers).
        $html = $this->renderPrintHtml($kecamatan);
        $htmlPath = base_path("tmp/krk-{$kecamatan}-{$stamp}.html");
        // All page data is inlined in the controller (rtrwGeojson + kecGeojson)
        // so the HTML works under file:// without any additional fetches.
        file_put_contents($htmlPath, $html);

        $node = (new ExecutableFinder())->find('node')
            ?? 'C:\\Program Files\\nodejs\\node.exe';
        // Symfony Process scrubs Windows env by default → node aborts on
        // CSPRNG init (exit 134). Manually pass the critical Windows vars.
        $env = array_filter([
            'SystemRoot'   => getenv('SystemRoot') ?: 'C:\\Windows',
            'SystemDrive'  => getenv('SystemDrive') ?: 'C:',
            'WINDIR'       => getenv('WINDIR') ?: 'C:\\Windows',
            'ComSpec'      => getenv('ComSpec') ?: 'C:\\Windows\\system32\\cmd.exe',
            'APPDATA'      => getenv('APPDATA'),
            'LOCALAPPDATA' => getenv('LOCALAPPDATA'),
            'USERPROFILE'  => getenv('USERPROFILE'),
            'HOMEDRIVE'    => getenv('HOMEDRIVE'),
            'HOMEPATH'     => getenv('HOMEPATH'),
            'TEMP'         => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP'          => getenv('TMP') ?: sys_get_temp_dir(),
            'PATH'         => getenv('PATH'),
            'PATHEXT'      => getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD',
            'OS'           => getenv('OS') ?: 'Windows_NT',
            'COMPUTERNAME' => getenv('COMPUTERNAME'),
            'USERNAME'     => getenv('USERNAME'),
            // Playwright Chromium lives in a project-cache dir on this host.
            'PLAYWRIGHT_BROWSERS_PATH' => getenv('PLAYWRIGHT_BROWSERS_PATH') ?: 'D:\\dev-cache\\ms-playwright',
        ], fn ($v) => $v !== false && $v !== null && $v !== '');
        // Pass HTML path as 3rd arg → node loads file:// instead of HTTP.
        $proc = new Process([$node, $script, $kecamatan, $outPath, $htmlPath], base_path(), $env, null, 120);
        $proc->run();

        if (!$proc->isSuccessful() || !file_exists($outPath)) {
            // Dump full stderr/stdout to a side file — laravel.log can mangle
            // multi-line node tracebacks. Keep one snapshot per failure.
            $debugPath = base_path('tmp/krk-error-' . $stamp . '.txt');
            file_put_contents($debugPath,
                "=== CMD ===\n" . $proc->getCommandLine() . "\n\n" .
                "=== EXIT ===\n" . $proc->getExitCode() . "\n\n" .
                "=== STDOUT ===\n" . $proc->getOutput() . "\n\n" .
                "=== STDERR ===\n" . $proc->getErrorOutput() . "\n"
            );
            \Log::error('[KrkExport] node failed — see ' . $debugPath . ' (exit ' . $proc->getExitCode() . ')');
            abort(500, 'PDF generation failed; debug at ' . $debugPath);
        }

        return response()->download($outPath, "Lampiran_KRK_{$kecamatan}_{$stamp}.pdf", [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }
}
