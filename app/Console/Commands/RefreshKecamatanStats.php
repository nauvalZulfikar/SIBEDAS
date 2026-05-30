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

    // Fungsi default untuk estimasi potensi retribusi bangunan tidak berizin.
    // detected_buildings belum punya label fungsi → pakai Hunian (tarif/m²
    // terendah) sebagai batas bawah yang konservatif. Fallback rate dipakai
    // kalau retribution_estimates belum di-seed.
    private const DEFAULT_FUNGSI = 'Fungsi Hunian';
    private const FALLBACK_RATE_PER_M2 = 9181.50;

    public function handle(): int
    {
        $t0 = microtime(true);
        $this->info('Refreshing kecamatan_stats…');

        // Tarif/m² untuk estimasi potensi retribusi (sumber tunggal:
        // retribution_estimates — tabel yang sama dipakai usulan_retribusi PBG).
        // $rates: map fungsi → tarif/m² (buat enriched: fungsi dari PBG ditolak).
        // $ratePerM2: default Hunian buat unenriched (bangunan tanpa fungsi).
        $rates = DB::table('retribution_estimates')
            ->whereNotNull('usulan_retribusi_per_m2')
            ->where('is_active', true)
            ->pluck('usulan_retribusi_per_m2', 'fungsi_bg');
        $ratePerM2 = (float) ($rates[self::DEFAULT_FUNGSI] ?? self::FALLBACK_RATE_PER_M2);

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

        // 2b) Realisasi retribusi per kecamatan (pbg_task_retributions ⨝ pbg_task_details
        // via pbg_task_uid; prasarana=0 di data ini → nilai = nilai_retribusi_bangunan).
        //   paid    = SK PBG Terbit (status 20) → retribusi sudah tertagih.
        //   pending = SKRD/proses pembayaran, belum terbit & belum rejected (3,9,22 = tanpa-izin).
        $retribByKec = DB::table('pbg_task_retributions as r')
            ->join('pbg_task_details as d', 'd.pbg_task_uid', '=', 'r.pbg_task_uid')
            ->whereIn('d.building_district_name', self::BS_DISTRICTS)
            ->groupBy('d.building_district_name')
            ->selectRaw("d.building_district_name AS kc,
                SUM(CASE WHEN d.status = 20 THEN r.nilai_retribusi_bangunan + COALESCE(r.nilai_prasarana,0) ELSE 0 END) AS paid,
                SUM(CASE WHEN d.status NOT IN (20,3,9,22) AND r.nilai_retribusi_bangunan > 0 THEN r.nilai_retribusi_bangunan + COALESCE(r.nilai_prasarana,0) ELSE 0 END) AS pending")
            ->get()->keyBy('kc');

        $now = now();
        $rows = 0;
        foreach (self::BS_DISTRICTS as $kc) {
            $pbg = $pbgByKec->get($kc);
            $pbgTotal = $pbg ? (int)$pbg->total : 0;
            $pbgTerbit = $pbg ? (int)$pbg->terbit : 0;
            $pbgProses = $pbg ? (int)$pbg->proses : 0;
            $pbgDitolak = $pbg ? (int)$pbg->ditolak : 0;
            $code = $districtCodeMap->get($kc);
            $retr = $retribByKec->get($kc);
            $actualPaid = $retr ? round((float)$retr->paid, 2) : 0.0;
            $actualPending = $retr ? round((float)$retr->pending, 2) : 0.0;

            foreach (self::AREA_BUCKETS as $bucket) {
                $q = DB::table('detected_buildings as db')
                    ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
                    ->where('db.kecamatan', $kc);
                // Pakai luas terkoreksi (actual_area_m2) kalau ada, fallback ke
                // estimated_area_m2 (legacy bbox) kalau belum di-recompute.
                if ($bucket > 0) $q->whereRaw('COALESCE(db.actual_area_m2, db.estimated_area_m2) >= ?', [$bucket]);

                $r = $q->selectRaw(
                    "COUNT(*) AS total,
                     SUM(CASE WHEN db.matched_pbg_task_id IS NULL THEN 1 ELSE 0 END) AS unmatched,
                     SUM(CASE WHEN db.matched_pbg_task_id IS NOT NULL AND pt.id IS NULL THEN 1 ELSE 0 END) AS orphan,
                     SUM(CASE WHEN pt.status = 20 THEN 1 ELSE 0 END) AS permit_valid,
                     SUM(CASE WHEN pt.status IN (3,9,22) THEN 1 ELSE 0 END) AS permit_rejected,
                     SUM(CASE WHEN pt.id IS NOT NULL AND pt.status IS NOT NULL AND pt.status NOT IN (3,9,20,22) THEN 1 ELSE 0 END) AS permit_in_process,
                     SUM(CASE WHEN pt.id IS NULL OR pt.status IN (3,9,22) THEN COALESCE(db.actual_area_m2, db.estimated_area_m2) ELSE 0 END) AS without_permit_area"
                )->first();

                $unmatched = (int)($r->unmatched ?? 0);
                $orphan = (int)($r->orphan ?? 0);
                $rejected = (int)($r->permit_rejected ?? 0);

                // Potensi retribusi tanpa izin = luas bangunan tanpa izin × tarif/m².
                // "Tanpa izin" = unmatched + orphan + ditolak (sama dgn without_permit_total).
                $wpArea = round((float)($r->without_permit_area ?? 0), 2);

                // Enriched = bangunan ditolak (status 3/9/22) yg punya fungsi PBG →
                // pakai tarif/m² sesuai fungsi (akurat). Sisanya unenriched → Hunian.
                $enrQ = DB::table('detected_buildings as db')
                    ->join('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
                    ->where('db.kecamatan', $kc)
                    ->whereIn('pt.status', [3, 9, 22])
                    ->whereNotNull('pt.function_type')
                    ->where('pt.function_type', '!=', '');
                if ($bucket > 0) $enrQ->whereRaw('COALESCE(db.actual_area_m2, db.estimated_area_m2) >= ?', [$bucket]);
                $enrRows = $enrQ->groupBy('pt.function_type')
                    ->selectRaw('pt.function_type AS fn, SUM(COALESCE(db.actual_area_m2, db.estimated_area_m2)) AS area')->get();

                $enrArea = 0.0; $enrRetribution = 0.0;
                foreach ($enrRows as $er) {
                    $rate = $rates[$er->fn] ?? null;
                    if ($rate === null) continue; // fungsi tak ada di tarif → biar jadi unenriched (Hunian)
                    $enrArea        += (float) $er->area;
                    $enrRetribution += (float) $er->area * (float) $rate;
                }

                // Source B — enrichment Google Places (property_enrichment.place_type)
                // utk bangunan tanpa-izin TANPA PBG (unmatched/orphan). PBG-ditolak
                // (Source A di atas) selalu menang; di sini hanya pt.id NULL (disjoint dr A).
                // confidence manual_review/low → dipaksa Hunian (jangan diam² Usaha).
                $enrB = DB::table('detected_buildings as db')
                    ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
                    ->join('property_enrichment as pe', 'pe.detected_building_id', '=', 'db.id')
                    ->join('place_type_function_mapping as m', 'm.place_type', '=', 'pe.place_type')
                    ->where('db.kecamatan', $kc)
                    ->whereRaw('(db.matched_pbg_task_id IS NULL OR pt.id IS NULL)')
                    // Jangan upgrade ke tarif Usaha/Campuran utk bbox blob yg luasnya
                    // sudah di-flag tak dipercaya — biarkan di pool unenriched (Hunian).
                    ->where('db.area_suspect', false);
                if ($bucket > 0) $enrB->whereRaw('COALESCE(db.actual_area_m2, db.estimated_area_m2) >= ?', [$bucket]);
                $enrBRows = $enrB
                    ->groupByRaw("CASE WHEN m.confidence = 'auto' THEN m.fungsi_bg ELSE 'Fungsi Hunian' END")
                    ->selectRaw("CASE WHEN m.confidence = 'auto' THEN m.fungsi_bg ELSE 'Fungsi Hunian' END AS fn,
                                 SUM(COALESCE(db.actual_area_m2, db.estimated_area_m2)) AS area")->get();
                foreach ($enrBRows as $er) {
                    // fungsi_bg dr mapping dijamin ada di retribution_estimates; default Hunian.
                    $rate = $rates[$er->fn] ?? $ratePerM2;
                    $enrArea        += (float) $er->area;
                    $enrRetribution += (float) $er->area * (float) $rate; // Keagamaan rate=0 → 0 (exempt)
                }

                $enrArea = round($enrArea, 2);
                $enrRetribution = round($enrRetribution, 2);

                // Total = enriched (tarif fungsi) + unenriched (sisa luas × Hunian).
                $unenrArea = max(0.0, $wpArea - $enrArea);
                $wpRetribution = round($enrRetribution + $unenrArea * $ratePerM2, 2);

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
                        'without_permit_area_m2'  => $wpArea,
                        'without_permit_retribution' => $wpRetribution,
                        'without_permit_enriched_area_m2'     => $enrArea,
                        'without_permit_enriched_retribution' => $enrRetribution,
                        // Realisasi retribusi (bucket-independent, sama spt pbg_*).
                        'actual_retribution_paid'    => $actualPaid,
                        'actual_retribution_pending' => $actualPending,
                        'total_potensi_combined'     => round($wpRetribution + $actualPending, 2),
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
