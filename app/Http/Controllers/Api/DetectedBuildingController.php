<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\DetectedBuilding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DetectedBuildingController extends Controller
{
    // Semua 31 kecamatan Kab. Bandung (sumber: cahyadsn/wilayah_boundaries, kode wilayah 32.04.*)
    private const BANDUNG_SELATAN_DISTRICTS = [
        'Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung',
        'Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot',
        'Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu',
        'Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali',
        'Rancaekek','Soreang','Solokanjeruk',
    ];

    // Bounding box spasial per kecamatan [sw_lat, sw_lng, ne_lat, ne_lng].
    // Urutan penting — yang pertama match adalah kecamatan yg ditugaskan (resolve overlap).
    // Field `building_district_name` di DB tidak presisi (Baleendah 181k mencakup luar kecamatan),
    // jadi kita reclassify berdasar koordinat.
    private const FUNCTION_TYPE_CATEGORIES = [
        'hunian'      => ['Hunian', 'Tempat Tinggal'],
        'usaha'       => ['Usaha'],
        'sosial'      => ['Sosial'],
        'prasarana'   => ['Prasarana'],
        'ibadah'      => ['Ibadah', 'Keagamaan'],
        'pendidikan'  => ['Pendidikan', 'Kebudayaan'],
        'multifungsi' => ['Multifungsi'],
    ];

    // High-level Usaha vs Non-Usaha grouping — mirrors the SpatialPlanning
    // building_function categorization used on the Dashboard Potensi Luar Sistem.
    // "Usaha" = commercial uses; "Non Usaha" = everything else.
    private const BUSINESS_CATEGORY_KEYWORDS = [
        'usaha'     => ['Usaha', 'Multifungsi'],
        'non_usaha' => ['Hunian', 'Tempat Tinggal', 'Sosial', 'Prasarana', 'Ibadah', 'Keagamaan', 'Pendidikan', 'Kebudayaan'],
    ];

    // pbg_task.status → kategori high-level. Hanya status 20 yang artinya izin benar-benar terbit.
    private const PBG_STATUS_CATEGORIES = [
        'terbit'  => [20],           // SK PBG Terbit
        'ditolak' => [3, 9, 22],     // Permohonan Dibatalkan/Ditolak, Sertifikat Dibekukan
        // 'proses' = semua selain di atas (dihitung di kode)
    ];

    /**
     * Scope ke Kab. Bandung Selatan — pakai kolom `kecamatan` (spatial, populated via polygon
     * GeoJSON BPS). Jauh lebih cepat & akurat daripada bbox OR logic, karena tiap baris udah
     * di-tag kecamatan yg bener pake point-in-polygon sekali saat migrasi.
     */
    private function scopeBandungSelatan($q) {
        return $q->whereIn('kecamatan', self::BANDUNG_SELATAN_DISTRICTS);
    }

    private function applyFunctionTypeFilter($q, string $cat): void
    {
        $keywords = self::FUNCTION_TYPE_CATEGORIES[strtolower($cat)] ?? [];
        if (!$keywords) return;
        $q->whereNotNull('matched_pbg_task_id')->whereHas('matchedPbgTask', function ($p) use ($keywords) {
            $p->where(function ($w) use ($keywords) {
                foreach ($keywords as $kw) $w->orWhere('function_type', 'LIKE', "%{$kw}%");
            });
        });
    }

    /**
     * Filter detected_buildings berdasarkan status PBG matched-nya.
     * 'terbit' / 'proses' / 'ditolak' = Dalam Sistem (matched ke pbg_task dengan status terkait).
     * 'luar_sistem' = Luar Sistem (unmatched / orphan FK / matched-tapi-PBG-ditolak).
     *
     * Implementasi pakai whereIn subquery, bukan whereHas (correlated EXISTS yang lambat
     * di 1M+ rows). MariaDB planner ngubah ini jadi semi-join + idx_matched_pbg_task_id.
     */
    private function applyPbgStatusFilter($q, string $status): void
    {
        $status = strtolower($status);
        $rejected = self::PBG_STATUS_CATEGORIES['ditolak'];
        $terbit   = self::PBG_STATUS_CATEGORIES['terbit'];

        if ($status === 'luar_sistem') {
            // Tanpa Izin Sah = (NULL FK) OR (FK tapi pbg_task hilang/orphan) OR (matched-tapi-status-ditolak).
            // Pakai NOT IN subquery untuk "matched_pbg_task_id punya pbg_task valid (status non-ditolak)";
            // semua yang gagal kondisi itu (NULL/orphan/ditolak-match) jatuh ke "Luar Sistem".
            $q->where(function ($w) use ($rejected) {
                $w->whereNull('matched_pbg_task_id')
                  ->orWhereNotIn('matched_pbg_task_id', function ($sub) use ($rejected) {
                      $sub->from('pbg_task')->whereNotNull('status')
                          ->whereNotIn('status', $rejected)
                          ->select('id');
                  });
            });
        } elseif ($status === 'terbit') {
            $q->whereNotNull('matched_pbg_task_id')->whereIn('matched_pbg_task_id', function ($sub) use ($terbit) {
                $sub->from('pbg_task')->whereIn('status', $terbit)->select('id');
            });
        } elseif ($status === 'ditolak') {
            $q->whereNotNull('matched_pbg_task_id')->whereIn('matched_pbg_task_id', function ($sub) use ($rejected) {
                $sub->from('pbg_task')->whereIn('status', $rejected)->select('id');
            });
        } elseif ($status === 'proses') {
            $q->whereNotNull('matched_pbg_task_id')->whereIn('matched_pbg_task_id', function ($sub) use ($terbit, $rejected) {
                $sub->from('pbg_task')->whereNotNull('status')
                    ->whereNotIn('status', array_merge($terbit, $rejected))
                    ->select('id');
            });
        }
    }

    /** Same as applyPbgStatusFilter but for raw DB queries with `db` + `pt` aliases. */
    private function applyPbgStatusToRaw($q, string $status): void
    {
        $status = strtolower($status);
        $rejected = self::PBG_STATUS_CATEGORIES['ditolak'];
        $terbit   = self::PBG_STATUS_CATEGORIES['terbit'];

        if ($status === 'luar_sistem') {
            $q->where(function ($w) use ($rejected) {
                $w->whereNull('db.matched_pbg_task_id')
                  ->orWhereNull('pt.id')
                  ->orWhereIn('pt.status', $rejected);
            });
        } elseif ($status === 'terbit') {
            $q->whereIn('pt.status', $terbit);
        } elseif ($status === 'ditolak') {
            $q->whereIn('pt.status', $rejected);
        } elseif ($status === 'proses') {
            $q->whereNotNull('pt.status')->whereNotIn('pt.status', array_merge($terbit, $rejected));
        }
    }

    private function applyBusinessCategoryFilter($q, string $cat): void
    {
        $keywords = self::BUSINESS_CATEGORY_KEYWORDS[strtolower($cat)] ?? [];
        if (!$keywords) return;
        $q->whereNotNull('matched_pbg_task_id')->whereHas('matchedPbgTask', function ($p) use ($keywords) {
            $p->where(function ($w) use ($keywords) {
                foreach ($keywords as $kw) $w->orWhere('function_type', 'LIKE', "%{$kw}%");
            });
        });
    }

    /** Apply business_category to a raw DB query builder that has joined `pt` (pbg_task) on `db.matched_pbg_task_id`. */
    private function applyBusinessCategoryToRaw($q, string $cat): void
    {
        $keywords = self::BUSINESS_CATEGORY_KEYWORDS[strtolower($cat)] ?? [];
        if (!$keywords) return;
        $q->whereNotNull('db.matched_pbg_task_id')->where(function ($w) use ($keywords) {
            foreach ($keywords as $kw) $w->orWhere('pt.function_type', 'LIKE', "%{$kw}%");
        });
    }

    /**
     * Resolve a leaf data source (mirrors Dashboard Potensi Luar Sistem categories) to the
     * list of kecamatan names that contain at least one record from that source. Filtering
     * detected_buildings by `kecamatan IN (...)` then narrows the map to areas where the
     * source actually has activity. Names are normalized to Title Case to match the
     * `detected_buildings.kecamatan` column produced by the BPS GeoJSON point-in-polygon.
     *
     * Sources without a usable kecamatan link (PDAM customers — coordinates only, no district;
     * spatial_plannings — no district at all) fall through to a category-keyword filter on the
     * matched PBG, which is the closest semantic equivalent.
     */
    private function applyDataSourceFilter($q, string $source, ?string $kbli = null): void
    {
        $source = strtolower($source);

        // Tata Ruang sources have no district info on their table — fall back to the existing
        // Usaha/Non-Usaha keyword match against matched PBG function_type.
        if ($source === 'tata_ruang_usaha')     { $this->applyBusinessCategoryFilter($q, 'usaha');     return; }
        if ($source === 'tata_ruang_non_usaha') { $this->applyBusinessCategoryFilter($q, 'non_usaha'); return; }

        $kecList = $this->getKecamatanListForSource($source, $kbli);
        if (empty($kecList)) return;
        // Constrain to Bandung Selatan + match Title Case format on detected_buildings.kecamatan.
        $kecList = array_values(array_intersect($kecList, self::BANDUNG_SELATAN_DISTRICTS));
        if (empty($kecList)) {
            // Source has data, but none of it falls in Bandung Selatan — return empty result.
            $q->whereRaw('1=0');
            return;
        }
        $q->whereIn('detected_buildings.kecamatan', $kecList);
    }

    /** Same as above but for raw DB queries that aliased the table as `db`. */
    private function applyDataSourceToRaw($q, string $source, ?string $kbli = null): void
    {
        $source = strtolower($source);
        if ($source === 'tata_ruang_usaha')     { $this->applyBusinessCategoryToRaw($q, 'usaha');     return; }
        if ($source === 'tata_ruang_non_usaha') { $this->applyBusinessCategoryToRaw($q, 'non_usaha'); return; }

        $kecList = $this->getKecamatanListForSource($source, $kbli);
        if (empty($kecList)) return;
        $kecList = array_values(array_intersect($kecList, self::BANDUNG_SELATAN_DISTRICTS));
        if (empty($kecList)) { $q->whereRaw('1=0'); return; }
        $q->whereIn('db.kecamatan', $kecList);
    }

    private function getKecamatanListForSource(string $source, ?string $kbli = null): array
    {
        return Cache::remember("source_kec_{$source}_" . md5((string) $kbli), 600, function () use ($source, $kbli) {
            switch ($source) {
                case 'pariwisata':
                    $q = DB::table('tourisms')
                        ->join('districts', 'districts.district_code', '=', 'tourisms.district_code')
                        ->select('districts.district_name');
                    if ($kbli) $q->where('tourisms.kbli_title', $kbli);
                    return $q->distinct()->pluck('district_name')->toArray();

                case 'reklame_survey':
                    return DB::table('advertisements')
                        ->join('districts', 'districts.district_code', '=', 'advertisements.district_code')
                        ->distinct()->pluck('districts.district_name')->toArray();

                case 'umkm':
                    return DB::table('umkms')
                        ->join('districts', 'districts.district_code', '=', 'umkms.district_code')
                        ->distinct()->pluck('districts.district_name')->toArray();

                case 'tax_reklame':
                case 'tax_restoran':
                case 'tax_hiburan':
                case 'tax_hotel':
                case 'tax_parkir':
                    $code = match ($source) {
                        'tax_reklame'  => 'REKLAME',
                        'tax_restoran' => 'Restoran',
                        'tax_hiburan'  => 'Hiburan',
                        'tax_hotel'    => 'Hotel',
                        'tax_parkir'   => 'Parkir',
                    };
                    $rows = DB::table('taxs')->where('tax_code', $code)
                        ->whereNotNull('subdistrict')->where('subdistrict', '!=', '')
                        ->distinct()->pluck('subdistrict')->toArray();
                    // Normalize "KECAMATAN SOREANG" → "Soreang". Skip "LUAR KABUPATEN BANDUNG" sentinel.
                    return array_values(array_filter(array_map(function ($s) {
                        $s = trim($s);
                        if (stripos($s, 'LUAR KABUPATEN') !== false) return null;
                        $s = preg_replace('/^KECAMATAN\s+/i', '', $s);
                        return ucwords(strtolower($s));
                    }, $rows)));

                case 'pdam':
                    // customers has lat/lng but no district. Use a coarse bbox against the
                    // Bandung Selatan kecamatan list — assume any customer with coordinates
                    // inside the BS bbox potentially exists in any of those kecamatan.
                    // Without a per-customer point-in-polygon (out of scope), we widen to
                    // all 18 BS kecamatan so the filter is effectively "show all".
                    return self::BANDUNG_SELATAN_DISTRICTS;
            }
            return [];
        });
    }

    public function index(Request $request): JsonResponse
    {
        $q = $this->scopeBandungSelatan(DetectedBuilding::query());
        if ($request->filled('status')) $q->where('verification_status', $request->status);
        if ($request->filled('source')) $q->where('detection_source', $request->source);
        if ($request->filled('district')) $q->where('building_district_name', $request->district);
        if ($request->boolean('unmatched_only')) $q->whereNull('matched_pbg_task_id');
        if ($request->filled('min_area')) $q->where('estimated_area_m2', '>=', $request->min_area);
        if ($request->filled('min_confidence')) $q->where('confidence_score', '>=', $request->min_confidence);
        if ($request->filled('function_type')) $this->applyFunctionTypeFilter($q, $request->function_type);
        if ($request->filled('business_category')) $this->applyBusinessCategoryFilter($q, $request->business_category);
        if ($request->filled('data_source')) $this->applyDataSourceFilter($q, $request->data_source, $request->get('kbli_title'));
        if ($request->filled(['sw_lat','sw_lng','ne_lat','ne_lng'])) {
            $q->whereBetween('latitude', [$request->sw_lat, $request->ne_lat])
              ->whereBetween('longitude', [$request->sw_lng, $request->ne_lng]);
        }
        return response()->json($q->orderByDesc('created_at')->paginate(min((int)$request->get('per_page',50),500)));
    }
    public function show(int $id): JsonResponse { return response()->json(DetectedBuilding::with('matchedPbgTask')->findOrFail($id)); }
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['verification_status'=>'required|in:unverified,confirmed_illegal,confirmed_legal,false_positive,under_review','notes'=>'nullable|string|max:1000']);
        $b = DetectedBuilding::findOrFail($id);
        $b->update(['verification_status'=>$request->verification_status,'notes'=>$request->notes??$b->notes,'verified_by'=>$request->user()?->id,'verified_at'=>now()]);
        $this->invalidateStatsCache();
        return response()->json($b);
    }

    /** Refresh snapshot kecamatan_stats — dipanggil manual oleh staff setelah verifikasi batch. */
    public function refreshStats(): JsonResponse
    {
        Artisan::call('kecamatan-stats:refresh');
        return response()->json(['status' => 'ok', 'refreshed_at' => now()->toIso8601String()]);
    }
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate(['ids'=>'required|array|max:100','ids.*'=>'integer|exists:detected_buildings,id','verification_status'=>'required|in:unverified,confirmed_illegal,confirmed_legal,false_positive,under_review']);
        DetectedBuilding::whereIn('id',$request->ids)->update(['verification_status'=>$request->verification_status,'verified_by'=>$request->user()?->id,'verified_at'=>now()]);
        $this->invalidateStatsCache();
        return response()->json(['updated'=>count($request->ids)]);
    }
    private const STATS_CACHE_KEY = 'detected_buildings_stats_kb_v13';

    public function stats(Request $request): JsonResponse
    {
        // Baca snapshot dari tabel kecamatan_stats (di-precompute oleh `php artisan kecamatan-stats:refresh`)
        // Fallback ke computeStats() kalau tabel kosong / bucket belum ada.
        $minArea = (int) $request->get('min_area', 0);
        $bucket = $this->snapBucket($minArea);
        $fnType = strtolower((string) $request->get('function_type', ''));
        $bCat   = strtolower((string) $request->get('business_category', ''));
        $dSrc   = strtolower((string) $request->get('data_source', ''));
        $pbgSt  = strtolower((string) $request->get('pbg_status', ''));
        $kbli   = (string) $request->get('kbli_title', '');

        // Kalau ada filter function_type / business_category / data_source / pbg_status,
        // gak bisa dipake dari snapshot (agregasi beda) — compute langsung.
        if ($fnType !== '' || $bCat !== '' || $dSrc !== '' || $pbgSt !== '') {
            $source = $request->get('source', 'all');
            $key = self::STATS_CACHE_KEY . "_{$source}_a{$minArea}_f{$fnType}_b{$bCat}_d{$dSrc}_p{$pbgSt}_k" . md5($kbli);
            $payload = Cache::remember($key, 600, fn () => $this->computeStats($request));
            return response()->json($payload);
        }

        $rows = \App\Models\KecamatanStat::where('min_area_bucket', $bucket)
            ->whereIn('kecamatan', self::BANDUNG_SELATAN_DISTRICTS)->get();
        if ($rows->isEmpty()) {
            return response()->json($this->computeStats($request));
        }

        $agg = [
            'total_detected' => 0, 'permit_valid' => 0, 'permit_in_process' => 0, 'permit_rejected' => 0,
            'match_orphan' => 0, 'unmatched' => 0, 'without_permit' => 0,
            'pbg_total' => 0, 'pbg_terbit' => 0, 'pbg_proses' => 0, 'pbg_ditolak' => 0,
        ];
        $unmatchedByDistrict = [];
        $pbgByDistrict = [];
        foreach ($rows as $r) {
            $agg['total_detected']      += $r->total_detected;
            $agg['permit_valid']        += $r->permit_valid_count;
            $agg['permit_in_process']   += $r->permit_in_process_count;
            $agg['permit_rejected']     += $r->permit_rejected_count;
            $agg['match_orphan']        += $r->orphan_count;
            $agg['unmatched']           += $r->unmatched_count;
            $agg['without_permit']      += $r->without_permit_total;
            $agg['pbg_total']           += $r->pbg_total;
            $agg['pbg_terbit']          += $r->pbg_terbit;
            $agg['pbg_proses']          += $r->pbg_proses;
            $agg['pbg_ditolak']         += $r->pbg_ditolak;
            $unmatchedByDistrict[$r->kecamatan] = $r->without_permit_total;
            $pbgByDistrict[$r->kecamatan] = [
                'terbit' => $r->pbg_terbit, 'proses' => $r->pbg_proses, 'ditolak' => $r->pbg_ditolak,
            ];
        }
        arsort($unmatchedByDistrict);

        return response()->json([
            'total_detected'        => $agg['total_detected'],
            'permit_valid'          => $agg['permit_valid'],
            'permit_in_process'     => $agg['permit_in_process'],
            'permit_rejected'       => $agg['permit_rejected'],
            'match_orphan'          => $agg['match_orphan'],
            'unmatched'             => $agg['unmatched'],
            'without_permit'        => $agg['without_permit'],
            'permit_rate'           => $agg['total_detected'] > 0 ? round($agg['permit_valid'] / $agg['total_detected'] * 100, 2) : 0,
            'unmatched_by_district' => $unmatchedByDistrict,
            'pbg_total'             => $agg['pbg_total'],
            'pbg_by_status_category'=> ['terbit' => $agg['pbg_terbit'], 'proses' => $agg['pbg_proses'], 'ditolak' => $agg['pbg_ditolak']],
            'pbg_by_district'       => $pbgByDistrict,
            'by_function_type'      => $this->functionTypeBreakdown(),
            'districts'             => self::BANDUNG_SELATAN_DISTRICTS,
            'snapshot_refreshed_at' => $rows->max('refreshed_at')?->toIso8601String(),
        ]);
    }

    private function snapBucket(int $minArea): int
    {
        foreach ([1000, 500, 200, 100, 50] as $b) if ($minArea >= $b) return $b;
        return 0;
    }

    private function functionTypeBreakdown(): array
    {
        return Cache::remember(self::STATS_CACHE_KEY . '_fn_types', 600, function () {
            $byFunctionType = array_fill_keys(array_keys(self::FUNCTION_TYPE_CATEGORIES), 0);
            $rows = DB::table('detected_buildings as db')
                ->join('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
                ->whereIn('db.kecamatan', self::BANDUNG_SELATAN_DISTRICTS)
                ->where('pt.status', 20)
                ->whereNotNull('pt.function_type')->where('pt.function_type', '!=', '')
                ->pluck('pt.function_type');
            foreach ($rows as $ft) {
                foreach (self::FUNCTION_TYPE_CATEGORIES as $key => $keywords) {
                    foreach ($keywords as $kw) {
                        if (stripos($ft, $kw) !== false) { $byFunctionType[$key]++; continue 3; }
                    }
                }
            }
            return $byFunctionType;
        });
    }

    private function computeStats(Request $request): array
    {
        $q = $this->scopeBandungSelatan(DetectedBuilding::query());
        if ($request->filled('source')) $q->where('detection_source',$request->source);
        $minArea = (int) $request->get('min_area', 0);
        if ($minArea > 0) $q->where('estimated_area_m2', '>=', $minArea);
        if ($request->filled('function_type')) $this->applyFunctionTypeFilter($q, $request->function_type);
        if ($request->filled('business_category')) $this->applyBusinessCategoryFilter($q, $request->business_category);
        if ($request->filled('data_source')) $this->applyDataSourceFilter($q, $request->data_source, $request->get('kbli_title'));
        if ($request->filled('pbg_status')) $this->applyPbgStatusFilter($q, $request->pbg_status);
        $total = (clone $q)->count();

        // Breakdown detected by PBG match status. Orphan = FK points to deleted pbg_task.
        $breakdownQ = DB::table('detected_buildings as db')
            ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
            ->whereIn('db.kecamatan', self::BANDUNG_SELATAN_DISTRICTS);
        if ($minArea > 0) $breakdownQ->where('db.estimated_area_m2', '>=', $minArea);
        if ($request->filled('function_type')) {
            $kw = self::FUNCTION_TYPE_CATEGORIES[strtolower($request->function_type)] ?? [];
            if ($kw) {
                $breakdownQ->whereNotNull('db.matched_pbg_task_id')
                    ->where(function ($w) use ($kw) {
                        foreach ($kw as $k) $w->orWhere('pt.function_type', 'LIKE', "%{$k}%");
                    });
            }
        }
        if ($request->filled('business_category')) $this->applyBusinessCategoryToRaw($breakdownQ, $request->business_category);
        if ($request->filled('data_source')) $this->applyDataSourceToRaw($breakdownQ, $request->data_source, $request->get('kbli_title'));
        if ($request->filled('pbg_status')) $this->applyPbgStatusToRaw($breakdownQ, $request->pbg_status);
        $breakdown = $breakdownQ->selectRaw(
                "SUM(CASE WHEN db.matched_pbg_task_id IS NULL THEN 1 ELSE 0 END) AS unmatched,
                 SUM(CASE WHEN db.matched_pbg_task_id IS NOT NULL AND pt.id IS NULL THEN 1 ELSE 0 END) AS orphan,
                 SUM(CASE WHEN pt.status = 20 THEN 1 ELSE 0 END) AS permit_valid,
                 SUM(CASE WHEN pt.status IN (3,9,22) THEN 1 ELSE 0 END) AS permit_rejected,
                 SUM(CASE WHEN pt.id IS NOT NULL AND pt.status IS NOT NULL AND pt.status NOT IN (3,9,20,22) THEN 1 ELSE 0 END) AS permit_in_process"
            )->first();

        $permitValid     = (int)($breakdown->permit_valid ?? 0);
        $permitProcess   = (int)($breakdown->permit_in_process ?? 0);
        $permitRejected  = (int)($breakdown->permit_rejected ?? 0);
        $orphan          = (int)($breakdown->orphan ?? 0);
        $unmatched       = (int)($breakdown->unmatched ?? 0);
        $withoutPermit   = $unmatched + $orphan + $permitRejected;

        // Per-kecamatan "tanpa izin" — group by kolom kecamatan yang udah pre-computed lewat PIP.
        $byDistrictQ = DB::table('detected_buildings as db')
            ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
            ->whereIn('db.kecamatan', self::BANDUNG_SELATAN_DISTRICTS)
            ->where(function ($w) {
                $w->whereNull('db.matched_pbg_task_id')
                  ->orWhereNull('pt.id')
                  ->orWhereIn('pt.status', [3, 9, 22]);
            });
        if ($minArea > 0) $byDistrictQ->where('db.estimated_area_m2', '>=', $minArea);
        if ($request->filled('function_type')) {
            $kw = self::FUNCTION_TYPE_CATEGORIES[strtolower($request->function_type)] ?? [];
            if ($kw) {
                $byDistrictQ->whereNotNull('db.matched_pbg_task_id')
                    ->where(function ($w) use ($kw) {
                        foreach ($kw as $k) $w->orWhere('pt.function_type', 'LIKE', "%{$k}%");
                    });
            }
        }
        if ($request->filled('business_category')) $this->applyBusinessCategoryToRaw($byDistrictQ, $request->business_category);
        if ($request->filled('data_source')) $this->applyDataSourceToRaw($byDistrictQ, $request->data_source, $request->get('kbli_title'));
        if ($request->filled('pbg_status')) $this->applyPbgStatusToRaw($byDistrictQ, $request->pbg_status);
        $byDistrict = $byDistrictQ
            ->select('db.kecamatan as kc', DB::raw('COUNT(*) as count'))
            ->groupBy('db.kecamatan')->orderByDesc('count')
            ->pluck('count', 'kc');

        // Jenis bangunan HANYA dari match ke SK PBG Terbit (status=20), biar honest
        $byFunctionType = array_fill_keys(array_keys(self::FUNCTION_TYPE_CATEGORIES), 0);
        $matchedTypes = DB::table('detected_buildings as db')
            ->join('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
            ->whereIn('db.kecamatan', self::BANDUNG_SELATAN_DISTRICTS)
            ->where('pt.status', 20)
            ->whereNotNull('pt.function_type')
            ->where('pt.function_type', '!=', '')
            ->pluck('pt.function_type');
        foreach ($matchedTypes as $ft) {
            foreach (self::FUNCTION_TYPE_CATEGORIES as $key => $keywords) {
                foreach ($keywords as $kw) {
                    if (stripos($ft, $kw) !== false) { $byFunctionType[$key]++; continue 3; }
                }
            }
        }

        // Breakdown PBG task di BS by status kategori (dari pbg_task_details — source SIMBG)
        $pbgRows = DB::table('pbg_task_details')
            ->whereIn('building_district_name', self::BANDUNG_SELATAN_DISTRICTS)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')->pluck('cnt', 'status');
        $pbgByCategory = ['terbit' => 0, 'proses' => 0, 'ditolak' => 0];
        $pbgTotal = 0;
        foreach ($pbgRows as $st => $cnt) {
            $pbgTotal += $cnt;
            if (in_array((int)$st, self::PBG_STATUS_CATEGORIES['terbit'], true))       $pbgByCategory['terbit']  += $cnt;
            elseif (in_array((int)$st, self::PBG_STATUS_CATEGORIES['ditolak'], true))  $pbgByCategory['ditolak'] += $cnt;
            else                                                                         $pbgByCategory['proses']  += $cnt;
        }

        // PBG per kecamatan (terbit / proses / ditolak) — untuk stacked bar di tabel "Data per Kecamatan"
        $pbgByDistrict = DB::table('pbg_task_details')
            ->whereIn('building_district_name', self::BANDUNG_SELATAN_DISTRICTS)
            ->select('building_district_name as kc',
                DB::raw('SUM(CASE WHEN status=20 THEN 1 ELSE 0 END) as terbit'),
                DB::raw('SUM(CASE WHEN status IN (3,9,22) THEN 1 ELSE 0 END) as ditolak'),
                DB::raw('SUM(CASE WHEN status IS NOT NULL AND status NOT IN (3,9,20,22) THEN 1 ELSE 0 END) as proses'))
            ->groupBy('building_district_name')
            ->get()->keyBy('kc')
            ->map(fn ($r) => ['terbit' => (int)$r->terbit, 'proses' => (int)$r->proses, 'ditolak' => (int)$r->ditolak]);

        return [
            'total_detected'       => $total,
            'permit_valid'         => $permitValid,     // SK PBG Terbit (status=20)
            'permit_in_process'    => $permitProcess,
            'permit_rejected'      => $permitRejected,  // Ditolak / Dibatalkan / Dibekukan
            'match_orphan'         => $orphan,          // FK dangling
            'unmatched'            => $unmatched,       // matched_pbg_task_id IS NULL
            'without_permit'       => $withoutPermit,   // unmatched + orphan + rejected
            'permit_rate'          => $total > 0 ? round($permitValid / $total * 100, 2) : 0,
            'unmatched_by_district'=> $byDistrict,
            'by_function_type'     => $byFunctionType,
            'pbg_total'            => $pbgTotal,
            'pbg_by_status_category'=> $pbgByCategory,
            'pbg_by_district'      => $pbgByDistrict,
            'districts'            => self::BANDUNG_SELATAN_DISTRICTS,
        ];
    }

    public function pbgGeojson(Request $request): JsonResponse
    {
        $q = DB::table('pbg_task_details as d')
            ->leftJoin('pbg_task as t', 't.uuid', '=', 'd.pbg_task_uid')
            ->whereIn('d.building_district_name', self::BANDUNG_SELATAN_DISTRICTS)
            ->whereNotNull('d.latitude')->whereNotNull('d.longitude');

        if ($request->filled('district')) $q->where('d.building_district_name', $request->district);
        if ($request->filled(['sw_lat','sw_lng','ne_lat','ne_lng'])) {
            $q->whereBetween('d.latitude', [$request->sw_lat, $request->ne_lat])
              ->whereBetween('d.longitude', [$request->sw_lng, $request->ne_lng]);
        }

        $cat = strtolower((string)$request->get('pbg_status', ''));
        if ($cat === 'terbit')  $q->whereIn('d.status', self::PBG_STATUS_CATEGORIES['terbit']);
        elseif ($cat === 'ditolak') $q->whereIn('d.status', self::PBG_STATUS_CATEGORIES['ditolak']);
        elseif ($cat === 'proses') $q->whereNotIn('d.status', array_merge(self::PBG_STATUS_CATEGORIES['terbit'], self::PBG_STATUS_CATEGORIES['ditolak']));

        if ($request->filled('function_type')) {
            $kw = self::FUNCTION_TYPE_CATEGORIES[strtolower($request->function_type)] ?? [];
            if ($kw) {
                $q->where(function ($w) use ($kw) {
                    foreach ($kw as $k) $w->orWhere('d.function_type', 'LIKE', "%{$k}%");
                });
            }
        }
        if ($request->filled('business_category')) {
            $kw = self::BUSINESS_CATEGORY_KEYWORDS[strtolower($request->business_category)] ?? [];
            if ($kw) {
                $q->where(function ($w) use ($kw) {
                    foreach ($kw as $k) $w->orWhere('d.function_type', 'LIKE', "%{$k}%");
                });
            }
        }
        if ($request->filled('data_source')) {
            $src = strtolower((string) $request->data_source);
            if ($src === 'tata_ruang_usaha' || $src === 'tata_ruang_non_usaha') {
                $cat = $src === 'tata_ruang_usaha' ? 'usaha' : 'non_usaha';
                $kw = self::BUSINESS_CATEGORY_KEYWORDS[$cat] ?? [];
                if ($kw) {
                    $q->where(function ($w) use ($kw) {
                        foreach ($kw as $k) $w->orWhere('d.function_type', 'LIKE', "%{$k}%");
                    });
                }
            } else {
                $kecList = $this->getKecamatanListForSource($src, $request->get('kbli_title'));
                $kecList = array_values(array_intersect($kecList, self::BANDUNG_SELATAN_DISTRICTS));
                if (empty($kecList)) {
                    $q->whereRaw('1=0');
                } else {
                    $q->whereIn('d.building_district_name', $kecList);
                }
            }
        }

        $limit = max(100, min((int)$request->get('limit', 3000), 5000));
        $rows = $q->select(
                'd.id','d.latitude','d.longitude','d.status','d.status_name','d.owner_name',
                'd.registration_number','d.function_type','d.total_area','d.building_district_name',
                'd.name_building','d.building_address'
            )->limit($limit)->get();

        $terbit  = self::PBG_STATUS_CATEGORIES['terbit'];
        $ditolak = self::PBG_STATUS_CATEGORIES['ditolak'];
        $features = $rows->map(function ($r) use ($terbit, $ditolak) {
            $s = (int)$r->status;
            $category = in_array($s, $terbit, true) ? 'terbit'
                : (in_array($s, $ditolak, true) ? 'ditolak' : 'proses');
            return [
                'type'=>'Feature',
                'geometry'=>['type'=>'Point','coordinates'=>[(float)$r->longitude,(float)$r->latitude]],
                'properties'=>[
                    'id'=>$r->id,'status'=>$s,'status_name'=>$r->status_name,'category'=>$category,
                    'owner_name'=>$r->owner_name,'registration_number'=>$r->registration_number,
                    'function_type'=>$r->function_type,'total_area'=>$r->total_area,
                    'district'=>$r->building_district_name,'name'=>$r->name_building,
                    'address'=>$r->building_address,
                ]
            ];
        });
        return response()->json(['type'=>'FeatureCollection','features'=>$features->values()]);
    }

    private function invalidateStatsCache(): void
    {
        // Sikat semua varian (tiap kombinasi source × min_area × function_type × business_category).
        // Cache key pakai prefix tetap, jadi bump version aja paling aman tiap ada perubahan shape.
        $sources = ['all','microsoft_footprints','sentinel_cv'];
        $areas   = [0, 50, 200, 500, 1000];
        $fns     = ['', 'hunian', 'usaha', 'sosial', 'prasarana', 'ibadah', 'pendidikan', 'multifungsi'];
        $bcats   = ['', 'usaha', 'non_usaha'];
        foreach ($sources as $s) foreach ($areas as $a) foreach ($fns as $f) foreach ($bcats as $b) {
            Cache::forget(self::STATS_CACHE_KEY . "_{$s}_a{$a}_f{$f}_b{$b}");
        }
    }
    public function geojson(Request $request): JsonResponse
    {
        $q = $this->scopeBandungSelatan(DetectedBuilding::query());
        // Kalau bbox disediakan, force pakai composite lat/lng index (MariaDB planner cenderung salah pilih index matched_pbg_task_id pas WHERE IS NULL)
        if ($request->filled(['sw_lat','sw_lng','ne_lat','ne_lng'])) {
            // Biarkan planner pilih index terbaik (biasanya idx_kecamatan karena whereIn(18)
            // plus bbox sangat selektif setelah di-filter kecamatan).
            $q->whereBetween('latitude',[$request->sw_lat,$request->ne_lat])->whereBetween('longitude',[$request->sw_lng,$request->ne_lng]);
        }
        if ($request->boolean('unmatched_only')) {
            // "Tanpa izin sah" = unmatched OR orphan FK OR match ke PBG ditolak
            $q->leftJoin('pbg_task as pt_flt', 'pt_flt.id', '=', 'detected_buildings.matched_pbg_task_id')
              ->where(function ($w) {
                  $w->whereNull('detected_buildings.matched_pbg_task_id')
                    ->orWhereNull('pt_flt.id')
                    ->orWhereIn('pt_flt.status', [3, 9, 22]);
              });
        }
        // Filter kecamatan: gunakan kolom yang udah pre-computed — presisi (point-in-polygon BPS) & fast (indexed).
        if ($request->filled('district') && in_array($request->district, self::BANDUNG_SELATAN_DISTRICTS, true)) {
            $q->where('detected_buildings.kecamatan', $request->district);
        }
        if ($request->filled('min_area')) $q->where('estimated_area_m2','>=',$request->min_area);
        if ($request->filled('function_type')) $this->applyFunctionTypeFilter($q, $request->function_type);
        if ($request->filled('business_category')) $this->applyBusinessCategoryFilter($q, $request->business_category);
        if ($request->filled('data_source')) $this->applyDataSourceFilter($q, $request->data_source, $request->get('kbli_title'));
        if ($request->filled('pbg_status')) $this->applyPbgStatusFilter($q, $request->pbg_status);
        $limit = max(100, min((int)$request->get('limit', 2000), 10000));
        // Kalau cuma 5000 dari 50k+ baris yg lolos, index scan bakal balikin yg paling selatan doang
        // (karena idx_latlng sortir lat ASC). Prioritaskan bangunan terbesar biar tersebar spasial
        // dan highlight yg paling relevan untuk enforcement.
        $buildings = $q->with(['matchedPbgTask:id,function_type,owner_name,registration_number,status,status_name'])
            ->select(
                'detected_buildings.id','detected_buildings.latitude','detected_buildings.longitude',
                'detected_buildings.estimated_area_m2','detected_buildings.matched_pbg_task_id',
                'detected_buildings.kecamatan'
            )
            ->orderByDesc('detected_buildings.estimated_area_m2')
            ->limit($limit)->get();
        $features = $buildings->map(function ($b) {
            $pbg = $b->matchedPbgTask;
            $permitState = 'tanpa_izin';
            if ($pbg) {
                if ((int)$pbg->status === 20)                        $permitState = 'terbit';
                elseif (in_array((int)$pbg->status, [3, 9, 22], true)) $permitState = 'ditolak';
                elseif ($pbg->status !== null)                        $permitState = 'proses';
            }
            return ['type'=>'Feature','geometry'=>['type'=>'Point','coordinates'=>[(float)$b->longitude,(float)$b->latitude]],
                'properties'=>[
                    'id'=>$b->id,
                    'area_m2'=>$b->estimated_area_m2,
                    'permit_state'=>$permitState,
                    'has_valid_permit'=>$permitState === 'terbit',
                    'district'=>$b->kecamatan,
                    'function_type'=>$pbg?->function_type,
                    'owner_name'=>$pbg?->owner_name,
                    'registration_number'=>$pbg?->registration_number,
                    'pbg_status_name'=>$pbg?->status_name,
                ]];
        });
        return response()->json(['type'=>'FeatureCollection','features'=>$features->values()]);
    }
}
