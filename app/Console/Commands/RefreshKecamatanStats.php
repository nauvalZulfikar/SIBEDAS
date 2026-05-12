<?php

namespace App\Console\Commands;

use App\Models\KecamatanStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshKecamatanStats extends Command
{
    protected $signature = 'kecamatan-stats:refresh';
    protected $description = 'Hitung ulang kecamatan_stats dari detected_buildings + pbg_task_details. Dipanggil saat init atau setelah verifikasi.';

    // Semua 31 kecamatan Kab Bandung
    private const BS_DISTRICTS = [
        'Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung',
        'Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot',
        'Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu',
        'Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali',
        'Rancaekek','Soreang','Solokanjeruk',
    ];

    private const AREA_BUCKETS = [0, 50, 100, 200, 500, 1000];

    public function handle(): int
    {
        $t0 = microtime(true);
        $this->info('Refreshing kecamatan_stats…');

        // 1) PBG summary per kecamatan — independen dari min_area bucket
        $pbgByKec = DB::table('pbg_task_details')
            ->whereIn('building_district_name', self::BS_DISTRICTS)
            ->select('building_district_name as kc',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status=20 THEN 1 ELSE 0 END) as terbit'),
                DB::raw('SUM(CASE WHEN status IS NOT NULL AND status NOT IN (3,9,20,22) THEN 1 ELSE 0 END) as proses'),
                DB::raw('SUM(CASE WHEN status IN (3,9,22) THEN 1 ELSE 0 END) as ditolak'))
            ->groupBy('building_district_name')
            ->get()->keyBy('kc');

        // 2) District code mapping dari tabel districts
        $districtCodeMap = DB::table('districts')
            ->whereIn('district_name', self::BS_DISTRICTS)
            ->pluck('district_code', 'district_name');

        $now = now();
        $rows = 0;
        foreach (self::BS_DISTRICTS as $kc) {
            $pbg = $pbgByKec->get($kc);
            $pbgTotal = $pbg ? (int)$pbg->total : 0;
            $pbgTerbit = $pbg ? (int)$pbg->terbit : 0;
            $pbgProses = $pbg ? (int)$pbg->proses : 0;
            $pbgDitolak = $pbg ? (int)$pbg->ditolak : 0;
            $code = $districtCodeMap->get($kc);

            foreach (self::AREA_BUCKETS as $bucket) {
                $q = DB::table('detected_buildings as db')
                    ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
                    ->where('db.kecamatan', $kc);
                if ($bucket > 0) $q->where('db.estimated_area_m2', '>=', $bucket);

                $r = $q->selectRaw(
                    "COUNT(*) AS total,
                     SUM(CASE WHEN db.matched_pbg_task_id IS NULL THEN 1 ELSE 0 END) AS unmatched,
                     SUM(CASE WHEN db.matched_pbg_task_id IS NOT NULL AND pt.id IS NULL THEN 1 ELSE 0 END) AS orphan,
                     SUM(CASE WHEN pt.status = 20 THEN 1 ELSE 0 END) AS permit_valid,
                     SUM(CASE WHEN pt.status IN (3,9,22) THEN 1 ELSE 0 END) AS permit_rejected,
                     SUM(CASE WHEN pt.id IS NOT NULL AND pt.status IS NOT NULL AND pt.status NOT IN (3,9,20,22) THEN 1 ELSE 0 END) AS permit_in_process"
                )->first();

                $unmatched = (int)($r->unmatched ?? 0);
                $orphan = (int)($r->orphan ?? 0);
                $rejected = (int)($r->permit_rejected ?? 0);

                KecamatanStat::updateOrCreate(
                    ['kecamatan' => $kc, 'min_area_bucket' => $bucket],
                    [
                        'district_code'           => $code,
                        'total_detected'          => (int)($r->total ?? 0),
                        'unmatched_count'         => $unmatched,
                        'orphan_count'            => $orphan,
                        'permit_valid_count'      => (int)($r->permit_valid ?? 0),
                        'permit_in_process_count' => (int)($r->permit_in_process ?? 0),
                        'permit_rejected_count'   => $rejected,
                        'without_permit_total'    => $unmatched + $orphan + $rejected,
                        'pbg_total'               => $pbgTotal,
                        'pbg_terbit'              => $pbgTerbit,
                        'pbg_proses'              => $pbgProses,
                        'pbg_ditolak'             => $pbgDitolak,
                        'refreshed_at'            => $now,
                    ]
                );
                $rows++;
            }
            $this->line("  ✓ {$kc}");
        }

        // Invalidate stats cache (biar endpoint langsung pakai data DB terbaru)
        foreach (['all','microsoft_footprints','sentinel_cv'] as $s) {
            foreach ([0, 50, 100, 200, 500, 1000] as $a) {
                foreach (['', 'hunian','usaha','sosial','prasarana','ibadah','pendidikan','multifungsi'] as $f) {
                    Cache::forget("detected_buildings_stats_kb_v9_{$s}_a{$a}_f{$f}");
                }
            }
        }
        Cache::forget('detected_buildings_stats_kb_v9_fn_types');

        $this->info(sprintf('Done. %d rows, %.1fs', $rows, microtime(true) - $t0));
        return self::SUCCESS;
    }
}
