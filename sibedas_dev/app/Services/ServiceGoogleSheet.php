<?php

namespace App\Services;

use App\Models\BigdataResume;
use App\Models\DataSetting;
use App\Models\ImportDatasource;
use App\Models\PbgTaskGoogleSheet;
use App\Models\SpatialPlanning;
use App\Models\RetributionCalculation;
use Carbon\Carbon;
use Exception;
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PbgTask;
class ServiceGoogleSheet
{
    protected $client;
    protected $service;
    protected $spreadsheetID;
    protected $service_sheets;
    protected $import_datasource;
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName("Sibedas Google Sheets API");
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
        $this->client->setAuthConfig(storage_path("app/teak-banner-450003-s8-ea05661d9db0.json"));
        $this->client->setAccessType("offline");

        $this->service = new Google_Service_Sheets($this->client);
        $this->spreadsheetID = env("SPREAD_SHEET_ID");

        $this->service_sheets = new Google_Service_Sheets($this->client);
    }

    public function run_service(){
        try{
            $this->sync_google_sheet_data();
            $this->sync_pbg_task_payments();
        }catch(Exception $e){
            throw $e;
        }
    }
    public function sync_google_sheet_data() {
        try {
            $sheet_data = $this->get_data_by_sheet(0);

            if (empty($sheet_data) || count($sheet_data) < 2) {
                Log::warning("sync_google_sheet_data: No valid data found.");
                throw new Exception("sync_google_sheet_data: No valid data found.");
            }

            $cleanValue = function ($value) {
                return (isset($value) && trim($value) !== '') ? trim($value) : null;
            };

            $mapUpsert = [];
            foreach(array_slice($sheet_data, 1) as $row){
                if(!is_array($row)){
                    continue;
                }

                $no_registrasi = $cleanValue($row[2] ?? null);

                // Apply the same logic from your SQL UPDATE
                if (strpos($no_registrasi, 'PBG-') === 0) {
                    $format_registrasi = $no_registrasi;
                } else {
                    $format_registrasi = sprintf(
                        "PBG-%s-%s-%s",
                        substr($no_registrasi, 0, 6) ?: '',
                        substr($no_registrasi, 7, 8) ?: '',
                        substr($no_registrasi, -2) ?: ''
                    );
                }

                $mapUpsert[] = [
                    'jenis_konsultasi' => $cleanValue($row[1] ?? null),
                    'no_registrasi' => $no_registrasi,
                    'formatted_registration_number' => $format_registrasi,
                    'nama_pemilik' => $cleanValue($row[3] ?? null),
                    'lokasi_bg' => $cleanValue($row[4] ?? null),
                    'fungsi_bg' => $cleanValue($row[5] ?? null),
                    'nama_bangunan' => $cleanValue($row[6] ?? null),
                    'tgl_permohonan' => $this->convertToDate($cleanValue($row[7] ?? null)),
                    'status_verifikasi' => $cleanValue($row[8] ?? null),
                    'status_permohonan' => $cleanValue($row[9] ?? null),
                    'alamat_pemilik' => $cleanValue($row[10] ?? null),
                    'no_hp' => $cleanValue($row[11] ?? null),
                    'email' => $cleanValue($row[12] ?? null),
                    'tanggal_catatan' => $this->convertToDate($cleanValue($row[13] ?? null)),
                    'catatan_kekurangan_dokumen' => $cleanValue($row[14] ?? null),
                    'gambar' => $cleanValue($row[15] ?? null),
                    'krk_kkpr' => $cleanValue($row[16] ?? null),
                    'no_krk' => $cleanValue($row[17] ?? null),
                    'lh' => $cleanValue($row[18] ?? null),
                    'ska' => $cleanValue($row[19] ?? null),
                    'keterangan' => $cleanValue($row[20] ?? null),
                    'helpdesk' => $cleanValue($row[21] ?? null),
                    'pj' => $cleanValue($row[22] ?? null),
                    'kepemilikan' => $cleanValue($row[24] ?? null),
                    'potensi_taru' => $cleanValue($row[25] ?? null),
                    'validasi_dinas' => $cleanValue($row[26] ?? null),
                    'kategori_retribusi' => $cleanValue($row[27] ?? null),
                    'no_urut_ba_tpt' => $cleanValue($row[28] ?? null),
                    'tanggal_ba_tpt' => $this->convertToDate($cleanValue($row[29] ?? null)),
                    'no_urut_ba_tpa' => $cleanValue($row[30] ?? null),
                    'tanggal_ba_tpa' => $this->convertToDate($cleanValue($row[31] ?? null)),
                    'no_urut_skrd' => $cleanValue($row[32] ?? null),
                    'tanggal_skrd' => $this->convertToDate($cleanValue($row[33] ?? null)),
                    'ptsp' => $cleanValue($row[34] ?? null),
                    'selesai_terbit' => $cleanValue($row[35] ?? null),
                    'tanggal_pembayaran' => $this->convertToDate($cleanValue($row[36] ?? null)),
                    'format_sts' => $cleanValue($row[37] ?? null),
                    'tahun_terbit' => (int) $cleanValue($row[38] ?? null),
                    'tahun_berjalan' => (int) $cleanValue($row[39] ?? null),
                    'kelurahan' => $cleanValue($row[40] ?? null),
                    'kecamatan' => $cleanValue($row[41] ?? null),
                    'lb' => $this->convertToDecimal($cleanValue($row[42] ?? 0)),
                    'tb' => $this->convertToDecimal($cleanValue($row[43] ?? 0)),
                    'jlb' => (int) $cleanValue($row[44] ?? null),
                    'unit' => (int) $cleanValue($row[45] ?? null),
                    'usulan_retribusi' => (int) $cleanValue($row[46] ?? null),
                    'nilai_retribusi_keseluruhan_simbg' => $this->convertToDecimal($cleanValue($row[47] ?? 0)),
                    'nilai_retribusi_keseluruhan_pad' => $this->convertToDecimal($cleanValue($row[48] ?? 0)),
                    'denda' => $this->convertToDecimal($cleanValue($row[49] ?? 0)),
                    'latitude' => $cleanValue($row[50] ?? null),
                    'longitude' => $cleanValue($row[51] ?? null),
                    'nik_nib' => $cleanValue($row[52] ?? null),
                    'dok_tanah' => $cleanValue($row[53] ?? null),
                    'temuan' => $cleanValue($row[54] ?? null),
                    'updated_at' => now()
                ];
            }

            // Count occurrences of each no_registrasi
            // Filter out null values before counting to avoid array_count_values error
            $registrationNumbers = array_filter(array_column($mapUpsert, 'no_registrasi'), function($value) {
                // Ensure only string and integer values are counted
                return $value !== null && $value !== '' && (is_string($value) || is_int($value));
            });
            
            // Additional safety check: convert all values to strings
            $registrationNumbers = array_map('strval', $registrationNumbers);
            
            $registrasiCounts = array_count_values($registrationNumbers);

            // Filter duplicates (those appearing more than once)
            $duplicates = array_filter($registrasiCounts, function ($count) {
                return $count > 1;
            });

            if (!empty($duplicates)) {
                Log::warning("Duplicate no_registrasi found", ['duplicates' => array_keys($duplicates)]);
            }

            // Remove duplicates before upsert - filter out entries with null no_registrasi
            $mapUpsert = collect($mapUpsert)
                ->filter(function($item) {
                    return !empty($item['no_registrasi']);
                })
                ->unique('no_registrasi')
                ->values()
                ->all();

            $batchSize = 1000;
            $chunks = array_chunk($mapUpsert, $batchSize);
            foreach ($chunks as $chunk) {
                PbgTaskGoogleSheet::upsert($chunk, ['no_registrasi']);
            }

            Log::info("sync google sheet done");
            return true;
        } catch (\Exception $e) {
            Log::error("sync_google_sheet_data failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }


    public function sync_big_data(){
        try {
            $sheet_big_data = $this->get_data_by_sheet();
            $data_setting_result = []; // Initialize result storage

            $found_section = null; // Track which section is found

            foreach ($sheet_big_data as $row) {
                // Check for section headers
                if (in_array("•PROSES PENERBITAN:", $row)) {
                    $found_section = "MENUNGGU_KLIK_DPMPTSP";
                } elseif (in_array("•BERKAS AKTUAL TERVERIFIKASI DINAS TEKNIS 2024:", $row)) {
                    $found_section = "REALISASI_TERBIT_PBG";
                } elseif (in_array("•TERPROSES DI DPUTR: belum selesai rekomtek'", $row)) {
                    $found_section = "PROSES_DINAS_TEKNIS";
                }

                // If a section is found and we reach "Grand Total", save the corresponding values
                if ($found_section && isset($row[0]) && trim($row[0]) === "Grand Total") {
                    if ($found_section === "MENUNGGU_KLIK_DPMPTSP") {
                        $data_setting_result["MENUNGGU_KLIK_DPMPTSP_COUNT"] = $this->convertToInteger($row[2]) ?? null;
                        $data_setting_result["MENUNGGU_KLIK_DPMPTSP_SUM"] = $this->convertToDecimal($row[3])  ?? null;
                    } elseif ($found_section === "REALISASI_TERBIT_PBG") {
                        $data_setting_result["REALISASI_TERBIT_PBG_COUNT"] = $this->convertToInteger($row[2]) ?? null;
                        $data_setting_result["REALISASI_TERBIT_PBG_SUM"] = $this->convertToDecimal($row[4]) ?? null;
                    } elseif ($found_section === "PROSES_DINAS_TEKNIS") {
                        $data_setting_result["PROSES_DINAS_TEKNIS_COUNT"] = $this->convertToInteger($row[2]) ?? null;
                        $data_setting_result["PROSES_DINAS_TEKNIS_SUM"] = $this->convertToDecimal($row[3]) ?? null;
                    }

                    // Reset section tracking after capturing "Grand Total"
                    $found_section = null;
                }
            }

            foreach ($data_setting_result as $key => $value) {
                // Ensure value is not null before saving to database
                $processedValue = 0; // Default to 0 instead of null
                if ($value !== null && $value !== '') {
                    if (strpos($key, '_COUNT') !== false) {
                        $processedValue = $this->convertToInteger($value) ?? 0;
                    } else {
                        $processedValue = $this->convertToDecimal($value) ?? 0;
                    }
                }

                DataSetting::updateOrCreate(
                    ["key" => $key], // Find by key
                    ["value" => $processedValue] // Update or insert value
                );
            }

            return true;
        } catch (\Exception $e) {
            // **Log error**
            Log::error("Error syncing Google Sheet data", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sync_leader_data(){
        $import_datasource = ImportDatasource::create([
            'message' => 'Processing leader data',
            'status' => 'processing',
            'start_time' => now(),
            'failed_uuid' => null
        ]);
        try {
            $sections = [
                'KEKURANGAN_POTENSI' => "DEVIASI TARGET DENGAN POTENSI TOTAL BERKAS",
                'TOTAL_POTENSI_BERKAS' => "•TOTAL BERKAS 2025",
                'BELUM_TERVERIFIKASI' => "•BERKAS AKTUAL BELUM TERVERIFIKASI (POTENSI):",
                'TERVERIFIKASI' => "•BERKAS AKTUAL TERVERIFIKASI DINAS TEKNIS 2025:",
                'NON_USAHA' => "•NON USAHA: HUNIAN, SOSBUD, KEAGAMAAN",
                'USAHA' => "•USAHA: USAHA, CAMPURAN, KOLEKTIF, PRASARANA",
                'PROSES_DINAS_TEKNIS' => "•TERPROSES DI DPUTR: belum selesai rekomtek'",
                'WAITING_KLIK_DPMPTSP' => "•TERPROSES DI PTSP: Pengiriman SKRD/ Validasi di PTSP",
                'REALISASI_TERBIT_PBG' => "•BERKAS YANG TERBIT PBG 2025:"
            ];

            $result = [];

            foreach ($sections as $key => $identifier) {
                $values = $this->get_values_from_section($identifier, [10, 11], 9);
                
                if (!empty($values)) {
                    $result[$key] = [
                        'identifier' => $identifier,
                        'total' => $values[0] ?? null,  // index 0 untuk total/jumlah
                        'nominal' => $values[1] ?? null // index 1 untuk nominal
                    ];
                }
            }

            BigdataResume::create([
                'import_datasource_id' => $import_datasource->id,
                'year' => date('Y'),
                'resume_type' => 'leader',
                // USAHA
                'business_count' => $this->convertToInteger($result['USAHA']['total'] ?? null) ?? 0,
                'business_sum' => $this->convertToDecimal($result['USAHA']['nominal'] ?? null) ?? 0,
                // NON USAHA
                'non_business_count' => $this->convertToInteger($result['NON_USAHA']['total'] ?? null) ?? 0,
                'non_business_sum' => $this->convertToDecimal($result['NON_USAHA']['nominal'] ?? null) ?? 0,
                // TERVERIFIKASI
                'verified_count' => $this->convertToInteger($result['TERVERIFIKASI']['total'] ?? null) ?? 0,
                'verified_sum' => $this->convertToDecimal($result['TERVERIFIKASI']['nominal'] ?? null) ?? 0,
                // BELUM TERVERIFIKASI
                'non_verified_count' => $this->convertToInteger($result['BELUM_TERVERIFIKASI']['total'] ?? null) ?? 0,
                'non_verified_sum' => $this->convertToDecimal($result['BELUM_TERVERIFIKASI']['nominal'] ?? null) ?? 0,
                // TOTAL POTENSI BERKAS
                'potention_count' => $this->convertToInteger($result['TOTAL_POTENSI_BERKAS']['total'] ?? null) ?? 0,
                'potention_sum' => $this->convertToDecimal($result['TOTAL_POTENSI_BERKAS']['nominal'] ?? null) ?? 0,
                // REALISASI TERBIT PBG
                'issuance_realization_pbg_count' => $this->convertToInteger($result['REALISASI_TERBIT_PBG']['total'] ?? null) ?? 0,
                'issuance_realization_pbg_sum' => $this->convertToDecimal($result['REALISASI_TERBIT_PBG']['nominal'] ?? null) ?? 0,
                // WAITING KLIK DPMPTSP
                'waiting_click_dpmptsp_count' => $this->convertToInteger($result['WAITING_KLIK_DPMPTSP']['total'] ?? null) ?? 0,
                'waiting_click_dpmptsp_sum' => $this->convertToDecimal($result['WAITING_KLIK_DPMPTSP']['nominal'] ?? null) ?? 0,
                // PROSES DINAS TEKNIS
                'process_in_technical_office_count' => $this->convertToInteger($result['PROSES_DINAS_TEKNIS']['total'] ?? null) ?? 0,
                'process_in_technical_office_sum' => $this->convertToDecimal($result['PROSES_DINAS_TEKNIS']['nominal'] ?? null) ?? 0,
                // TATA RUANG
                'spatial_count' => $this->getSpatialPlanningWithCalculationCount(),
                'spatial_sum' => $this->getSpatialPlanningCalculationSum(),
                'business_rab_count' =>  0,
                'business_krk_count' =>  0,
                'non_business_rab_count' => 0,
                'non_business_krk_count' => 0,
                'non_business_dlh_count' => 0,
            ]);

            // Save data settings
            $dataSettings = [
                'KEKURANGAN_POTENSI' => $result['KEKURANGAN_POTENSI']['nominal'] ?? null,
                'REALISASI_TERBIT_PBG_COUNT' => $result['REALISASI_TERBIT_PBG']['total'] ?? null,
                'REALISASI_TERBIT_PBG_SUM' => $result['REALISASI_TERBIT_PBG']['nominal'] ?? null,
                'MENUNGGU_KLIK_DPMPTSP_COUNT' => $result['WAITING_KLIK_DPMPTSP']['total'] ?? null,
                'MENUNGGU_KLIK_DPMPTSP_SUM' => $result['WAITING_KLIK_DPMPTSP']['nominal'] ?? null,
                'PROSES_DINAS_TEKNIS_COUNT' => $result['PROSES_DINAS_TEKNIS']['total'] ?? null,
                'PROSES_DINAS_TEKNIS_SUM' => $result['PROSES_DINAS_TEKNIS']['nominal'] ?? null,
            ];

            foreach ($dataSettings as $key => $value) {
                // Ensure value is not null before saving to database
                $processedValue = 0; // Default to 0 instead of null
                if ($value !== null && $value !== '') {
                    // Try to convert to appropriate type based on key name
                    if (strpos($key, '_COUNT') !== false) {
                        $processedValue = $this->convertToInteger($value) ?? 0;
                    } else {
                        $processedValue = $this->convertToDecimal($value) ?? 0;
                    }
                }

                DataSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $processedValue]
                );
            }

            $import_datasource->update([
                'status' => 'success',
                'response_body' => json_encode($result),
                'message' => 'Leader data synced',
                'finish_time' => now()
            ]);
            return $result;
        } catch (\Exception $e) {
            Log::error("Error syncing leader data", ['error' => $e->getMessage()]);
            $import_datasource->update([
                'status' => 'failed',
                'message' => 'Leader data sync failed',
                'finish_time' => now()
            ]);
            throw $e;
        }
    }

    public function get_big_resume_data(){
        try {
            $sections = [
                'KEKURANGAN_POTENSI' => "DEVIASI TARGET DENGAN POTENSI TOTAL BERKAS",
                'TOTAL_POTENSI_BERKAS' => "•TOTAL BERKAS 2025",
                'BELUM_TERVERIFIKASI' => "•BERKAS AKTUAL BELUM TERVERIFIKASI (POTENSI):",
                'TERVERIFIKASI' => "•BERKAS AKTUAL TERVERIFIKASI DINAS TEKNIS 2025:",
                'NON_USAHA' => "•NON USAHA: HUNIAN, SOSBUD, KEAGAMAAN",
                'USAHA' => "•USAHA: USAHA, CAMPURAN, KOLEKTIF, PRASARANA",
                'PROSES_DINAS_TEKNIS' => "•TERPROSES DI DPUTR: belum selesai rekomtek'",
                'WAITING_KLIK_DPMPTSP' => "•TERPROSES DI PTSP: Pengiriman SKRD/ Validasi di PTSP",
                'REALISASI_TERBIT_PBG' => "•BERKAS YANG TERBIT PBG 2025:"
            ];

            $result = [];

            foreach ($sections as $key => $identifier) {
                $values = $this->get_values_from_section($identifier, [10, 11], 9);
                
                if (!empty($values)) {
                    $result[$key] = [
                        'identifier' => $identifier,
                        'total' => $values[0] ?? null,  // index 0 untuk total/jumlah
                        'nominal' => $values[1] ?? null // index 1 untuk nominal
                    ];
                }
            }

            // Save data settings
            $dataSettings = [
                'KEKURANGAN_POTENSI' => $this->convertToDecimal($result['KEKURANGAN_POTENSI']['nominal']) ?? 0,
                'REALISASI_TERBIT_PBG_COUNT' => $this->convertToInteger($result['REALISASI_TERBIT_PBG']['total']) ?? 0,
                'REALISASI_TERBIT_PBG_SUM' => $this->convertToDecimal($result['REALISASI_TERBIT_PBG']['nominal']) ?? 0,
                'MENUNGGU_KLIK_DPMPTSP_COUNT' => $this->convertToInteger($result['WAITING_KLIK_DPMPTSP']['total']) ?? 0,
                'MENUNGGU_KLIK_DPMPTSP_SUM' => $this->convertToDecimal($result['WAITING_KLIK_DPMPTSP']['nominal']) ?? 0,
                'PROSES_DINAS_TEKNIS_COUNT' => $this->convertToInteger($result['PROSES_DINAS_TEKNIS']['total']) ?? 0,
                'PROSES_DINAS_TEKNIS_SUM' =>  $this->convertToDecimal($result['PROSES_DINAS_TEKNIS']['nominal']) ?? 0,
                'SPATIAL_PLANNING_COUNT' => $this->getSpatialPlanningWithCalculationCount(),
                'SPATIAL_PLANNING_SUM' => $this->getSpatialPlanningCalculationSum()
            ];

            foreach ($dataSettings as $key => $value) {
                // Ensure value is not null before saving to database
                $processedValue = 0; // Default to 0 instead of null
                if ($value !== null && $value !== '') {
                    // Try to convert to appropriate type based on key name
                    if (strpos($key, '_COUNT') !== false) {
                        $processedValue = $this->convertToInteger($value) ?? 0;
                    } else {
                        $processedValue = $this->convertToDecimal($value) ?? 0;
                    }
                }

                DataSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $processedValue]
                );
            }
            return $dataSettings;
        }catch(Exception $exception){
            Log::error("Error getting big resume data", ['error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    /**
     * Get sheet data where the first row is treated as headers, and subsequent rows
     * are returned as associative arrays keyed by header names. Supports selecting
     * a contiguous column range plus additional specific columns.
     *
     * Example: get_sheet_data_with_headers_range('Data', 'A', 'AX', ['BX'])
     *
     * @param string $sheet_name
     * @param string $start_column_letter Inclusive start column letter (e.g., 'A')
     * @param string $end_column_letter Inclusive end column letter (e.g., 'AX')
     * @param array $extra_column_letters Additional discrete column letters (e.g., ['BX'])
     * @return array{headers: array<int,string>, data: array<int,array<string,?string>>, selected_columns: array<int,int>}
     */
    public function get_sheet_data_with_headers_range(string $sheet_name, string $start_column_letter, string $end_column_letter, array $extra_column_letters = [])
    {
        try {
            $sheet_data = $this->get_data_by_sheet_name($sheet_name);

            if (empty($sheet_data)) {
                Log::warning("No data found in sheet", ['sheet_name' => $sheet_name]);
                return [
                    'headers' => [],
                    'data' => [],
                    'selected_columns' => []
                ];
            }

            // Build selected column indices: range A..AX and extras like BX
            $selected_indices = $this->expandColumnRangeToIndices($start_column_letter, $end_column_letter);
            foreach ($extra_column_letters as $letter) {
                $selected_indices[] = $this->columnLetterToIndex($letter);
            }
            // Ensure unique and sorted
            $selected_indices = array_values(array_unique($selected_indices));
            sort($selected_indices);

            $result = [
                'headers' => [],
                'data' => [],
                'selected_columns' => $selected_indices
            ];

            foreach ($sheet_data as $row_index => $row) {
                if (!is_array($row)) continue;

                if ($row_index === 0) {
                    // First row contains headers (by selected columns)
                    foreach ($selected_indices as $col_index) {
                        $raw = isset($row[$col_index]) ? trim((string) $row[$col_index]) : '';
                        // Fallback to column letter if empty
                        $header = $raw !== '' ? $raw : $this->indexToColumnLetter($col_index);
                        $result['headers'][$col_index] = $this->normalizeHeader($header);
                    }
                } else {
                    $row_assoc = [];
                    $has_data = false;
                    foreach ($selected_indices as $col_index) {
                        $header = $result['headers'][$col_index] ?? $this->normalizeHeader($this->indexToColumnLetter($col_index));
                        $value = isset($row[$col_index]) ? trim((string) $row[$col_index]) : '';
                        $row_assoc[$header] = ($value === '') ? null : $value;
                        if ($value !== '') {
                            $has_data = true;
                        }
                    }
                    if ($has_data) {
                        $result['data'][] = $row_assoc;
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Error getting sheet data with headers", [
                'sheet_name' => $sheet_name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Convert a column letter (e.g., 'A', 'Z', 'AA', 'AX', 'BX') to a zero-based index (A=0)
     */
    private function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper(trim($letter));
        $length = strlen($letter);
        $index = 0;
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        return $index - 1; // zero-based
    }

    /**
     * Convert zero-based column index to column letter (0='A')
     */
    private function indexToColumnLetter(int $index): string
    {
        $index += 1; // make 1-based for calculation
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr($mod + ord('A')) . $letters;
            $index = intdiv($index - 1, 26);
        }
        return $letters;
    }

    /**
     * Expand a column range like 'A'..'AX' to zero-based indices array
     */
    private function expandColumnRangeToIndices(string $start_letter, string $end_letter): array
    {
        $start = $this->columnLetterToIndex($start_letter);
        $end = $this->columnLetterToIndex($end_letter);
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }
        return range($start, $end);
    }

    /**
     * Normalize header: trim, lowercase, replace spaces with underscore, remove non-alnum/underscore
     */
    private function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = strtolower($header);
        $header = preg_replace('/\s+/', '_', $header);
        $header = preg_replace('/[^a-z0-9_]/', '', $header);
        return $header;
    }

    public function sync_pbg_task_payments(){
        try {
            $sheetName = 'Data';
            $startLetter = 'A';
            $endLetter = 'AX';
            $extraLetters = ['BF'];

            // Fetch header row only (row 1) across A..BF and build header/selection
            $headerRange = sprintf('%s!%s1:%s1', $sheetName, $startLetter, 'BF');
            $headerResponse = $this->service->spreadsheets_values->get($this->spreadsheetID, $headerRange);
            $headerRow = $headerResponse->getValues()[0] ?? [];
            if (empty($headerRow)) {
                Log::warning("No header row found in sheet", ['sheet' => $sheetName]);
                return ['success' => false, 'message' => 'No header row found'];
            }

            // Selected indices: A..AX plus BF
            $selected_indices = $this->expandColumnRangeToIndices($startLetter, $endLetter);
            foreach ($extraLetters as $letter) {
                $selected_indices[] = $this->columnLetterToIndex($letter);
            }
            $selected_indices = array_values(array_unique($selected_indices));
            sort($selected_indices);

            // Build normalized headers map (index -> header)
            $headers = [];
            foreach ($selected_indices as $colIdx) {
                $raw = isset($headerRow[$colIdx]) ? trim((string) $headerRow[$colIdx]) : '';
                $header = $raw !== '' ? $raw : $this->indexToColumnLetter($colIdx);
                $headers[$colIdx] = $this->normalizeHeader($header);
            }

            // Log environment and header diagnostics
            Log::info('sync_pbg_task_payments: diagnostics', [
                'spreadsheet_id' => $this->spreadsheetID,
                'sheet' => $sheetName,
                'selected_indices_count' => count($selected_indices)
            ]);

            // Validate that expected headers exist after normalization before truncating table
            $expectedHeaders = [
                'no','jenis_konsultasi','no_registrasi','nama_pemilik','lokasi_bg','fungsi_bg','nama_bangunan',
                'tgl_permohonan','status_verifikasi','status_permohonan','alamat_pemilik','no_hp','email',
                'tanggal_catatan','catatan_kekurangan_dokumen','gambar','krkkkpr','no_krk','lh','ska','keterangan',
                'helpdesk','pj','operator_pbg','kepemilikan','potensi_taru','validasi_dinas','kategori_retribusi',
                'no_urut_ba_tpt_20250001','tanggal_ba_tpt','no_urut_ba_tpa','tanggal_ba_tpa','no_urut_skrd_20250001',
                'tanggal_skrd','ptsp','selesai_terbit','tanggal_pembayaran_yyyymmdd','format_sts','tahun_terbit',
                'tahun_berjalan','kelurahan','kecamatan','lb','tb','jlb','unit','usulan_retribusi',
                'nilai_retribusi_keseluruhan_simbg','nilai_retribusi_keseluruhan_pad','denda','usaha__non_usaha'
            ];

            $normalizedHeaderValues = array_values($headers);
            $overlap = array_intersect($expectedHeaders, $normalizedHeaderValues);

            if (count($overlap) < 10) { // too few matching headers, likely wrong sheet or headers changed
                Log::error('sync_pbg_task_payments: header mismatch detected', [
                    'expected_sample' => array_slice($expectedHeaders, 0, 15),
                    'found_sample' => array_slice($normalizedHeaderValues, 0, 30),
                    'match_count' => count($overlap)
                ]);
                return ['success' => false, 'message' => 'Header mismatch - aborting to prevent null inserts'];
            }

            // Truncate table and restart identity (only after header validation)
            Schema::disableForeignKeyConstraints();
            DB::table('pbg_task_payments')->truncate();
            Schema::enableForeignKeyConstraints();

            // Map header -> db column
            $map = [
                'no' => 'row_no',
                'jenis_konsultasi' => 'consultation_type',
                'no_registrasi' => 'source_registration_number',
                'nama_pemilik' => 'owner_name',
                'lokasi_bg' => 'building_location',
                'fungsi_bg' => 'building_function',
                'nama_bangunan' => 'building_name',
                'tgl_permohonan' => 'application_date_raw',
                'status_verifikasi' => 'verification_status',
                'status_permohonan' => 'application_status',
                'alamat_pemilik' => 'owner_address',
                'no_hp' => 'owner_phone',
                'email' => 'owner_email',
                'tanggal_catatan' => 'note_date_raw',
                'catatan_kekurangan_dokumen' => 'document_shortage_note',
                'gambar' => 'image_url',
                'krkkkpr' => 'krk_kkpr',
                'no_krk' => 'krk_number',
                'lh' => 'lh',
                'ska' => 'ska',
                'keterangan' => 'remarks',
                'helpdesk' => 'helpdesk',
                'pj' => 'person_in_charge',
                'operator_pbg' => 'pbg_operator',
                'kepemilikan' => 'ownership',
                'potensi_taru' => 'taru_potential',
                'validasi_dinas' => 'agency_validation',
                'kategori_retribusi' => 'retribution_category',
                'no_urut_ba_tpt_20250001' => 'ba_tpt_number',
                'tanggal_ba_tpt' => 'ba_tpt_date_raw',
                'no_urut_ba_tpa' => 'ba_tpa_number',
                'tanggal_ba_tpa' => 'ba_tpa_date_raw',
                'no_urut_skrd_20250001' => 'skrd_number',
                'tanggal_skrd' => 'skrd_date_raw',
                'ptsp' => 'ptsp_status',
                'selesai_terbit' => 'issued_status',
                'tanggal_pembayaran_yyyymmdd' => 'payment_date_raw',
                'format_sts' => 'sts_format',
                'tahun_terbit' => 'issuance_year',
                'tahun_berjalan' => 'current_year',
                'kelurahan' => 'village',
                'kecamatan' => 'district',
                'lb' => 'building_area',
                'tb' => 'building_height',
                'jlb' => 'floor_count',
                'unit' => 'unit_count',
                'usulan_retribusi' => 'proposed_retribution',
                'nilai_retribusi_keseluruhan_simbg' => 'retribution_total_simbg',
                'nilai_retribusi_keseluruhan_pad' => 'retribution_total_pad',
                'denda' => 'penalty_amount',
                'usaha__non_usaha' => 'business_category',
            ];

            // We'll build registration map lazily per chunk to limit memory
            $regToTask = [];

            // Build and insert in small batches to avoid high memory usage
            $batch = [];
            $inserted = 0;
            // Stream rows in chunks from API to avoid loading full sheet
            $rowStart = 2; // data starts from row 2
            $chunkRowSize = 1000; // number of rows per chunk
            $inserted = 0;
            while (true) {
                $rowEnd = $rowStart + $chunkRowSize - 1;
                $range = sprintf('%s!%s%d:%s%d', $sheetName, $startLetter, $rowStart, 'BF', $rowEnd);
                $resp = $this->service->spreadsheets_values->get($this->spreadsheetID, $range);
                $values = $resp->getValues() ?? [];

                
                if (empty($values)) {
                    break; // no more rows
                }
                
                Log::info('Chunk fetched', [
                    'rowStart' => $rowStart,
                    'rowEnd'   => $rowEnd,
                    'count'    => count($values)
                ]);
                // Preload registration map for this chunk
                $chunkRegs = [];
                foreach ($values as $row) {
                    foreach ($selected_indices as $colIdx) {
                        // find normalized header for this index
                        $h = $headers[$colIdx] ?? null;
                        if ($h === 'no_registrasi') {
                            $val = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : '';
                            if ($val !== '') { $chunkRegs[$val] = true; }
                        }
                    }
                }
                if (!empty($chunkRegs)) {
                    $keys = array_keys($chunkRegs);
                    $tasks = PbgTask::whereIn('registration_number', $keys)->get(['id','uuid','registration_number']);
                    foreach ($tasks as $task) {
                        $regToTask[trim($task->registration_number)] = ['id' => $task->id, 'uuid' => $task->uuid];
                    }
                }

                // Build and insert this chunk
                $batch = [];
                foreach ($values as $rowIndex => $row) {
                    $record = [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Map row values by headers
                    $rowByHeader = [];
                    foreach ($selected_indices as $colIdx) {
                        $h = $headers[$colIdx] ?? null;
                        if ($h === null) continue;
                        $rowByHeader[$h] = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : null;
                        if ($rowByHeader[$h] === '') $rowByHeader[$h] = null;
                    }

                    // Log first non-empty row mapping for diagnostics
                    if ($rowIndex === 0) {
                        $nonEmptySample = [];
                        foreach ($rowByHeader as $k => $v) {
                            if ($v !== null && count($nonEmptySample) < 10) { $nonEmptySample[$k] = $v; }
                        }
                        Log::info('sync_pbg_task_payments: first row sample after normalization', [
                            'sample' => $nonEmptySample
                        ]);
                    }

                    // Skip if this row looks like a header row
                    $headerCheckKeys = ['no','jenis_konsultasi','no_registrasi'];
                    $headerMatches = 0;
                    foreach ($headerCheckKeys as $hk) {
                        if (!array_key_exists($hk, $rowByHeader)) { continue; }
                        $val = $rowByHeader[$hk];
                        if ($val === null) { continue; }
                        if ($this->normalizeHeader($val) === $hk) {
                            $headerMatches++;
                        }
                    }
                    if ($headerMatches >= 2) {
                        continue; // looks like a repeated header row, skip
                    }

                    // Skip if the entire row is empty (no values)
                    $hasAnyData = false;
                    foreach ($rowByHeader as $v) {
                        if ($v !== null && $v !== '') { $hasAnyData = true; break; }
                    }
                    if (!$hasAnyData) { continue; }

                    foreach ($map as $header => $column) {
                        $value = $rowByHeader[$header] ?? null;

                        switch ($column) {
                            case 'row_no':
                            case 'floor_count':
                            case 'unit_count':
                            case 'issuance_year':
                            case 'current_year':
                                $record[$column] = ($value === null || $value === '') ? null : (int) $value;
                                break;
                            case 'application_date_raw':
                            case 'note_date_raw':
                            case 'ba_tpt_date_raw':
                            case 'ba_tpa_date_raw':
                            case 'skrd_date_raw':
                            case 'payment_date_raw':
                                $record[$column] = $this->convertToDate($value);
                                break;
                            case 'building_area':
                            case 'building_height':
                            case 'proposed_retribution':
                            case 'retribution_total_simbg':
                            case 'retribution_total_pad':
                            case 'penalty_amount':
                                $record[$column] = $this->convertToDecimal($value);
                                break;
                            default:
                                if (is_string($value)) { $value = trim($value); }
                                $record[$column] = ($value === '' ? null : $value);
                        }
                    }

                    // Final trim pass
                    foreach ($record as $k => $v) {
                        if (is_string($v)) {
                            $t = trim($v);
                            $record[$k] = ($t === '') ? null : $t;
                        }
                    }

                    // Resolve relation
                    $sourceReg = $rowByHeader['no_registrasi'] ?? null;
                    if (is_string($sourceReg)) { $sourceReg = trim($sourceReg); }
                    if (!empty($sourceReg) && isset($regToTask[$sourceReg])) {
                        $record['pbg_task_id'] = $regToTask[$sourceReg]['id'];
                        $record['pbg_task_uid'] = $regToTask[$sourceReg]['uuid'];
                    } else {
                        $record['pbg_task_id'] = null;
                        $record['pbg_task_uid'] = null;
                    }

                    $batch[] = $record;
                }

                if (!empty($batch)) {
                    \App\Models\PbgTaskPayment::insert($batch);
                    $inserted += count($batch);
                }

                // next chunk
                $rowStart = $rowEnd + 1;
                if (function_exists('gc_collect_cycles')) { gc_collect_cycles(); }
            }

            Log::info('PBG Task Payments reloaded from sheet', ['inserted' => $inserted]);

            return ['success' => true, 'inserted' => $inserted];

        } catch (\Exception $e) {
            Log::error("Error syncing PBG task payments", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function get_data_by_sheet($no_sheet = 8){
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetID);
        $sheets = $spreadsheet->getSheets();
        $sheetTitle = $sheets[$no_sheet]->getProperties()->getTitle();
        $range = "{$sheetTitle}";
        $response = $this->service->spreadsheets_values->get($this->spreadsheetID, $range);
        $values = $response->getValues();
        return!empty($values)? $values : [];
    }

    private function get_data_by_sheet_name($sheet_name){
        try {
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetID);
            $sheets = $spreadsheet->getSheets();
            
            // Find sheet by name
            $targetSheet = null;
            foreach ($sheets as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheet_name) {
                    $targetSheet = $sheet;
                    break;
                }
            }
            
            if (!$targetSheet) {
                Log::warning("Sheet not found", ['sheet_name' => $sheet_name]);
                return [];
            }
            
            $range = "{$sheet_name}";
            $response = $this->service->spreadsheets_values->get($this->spreadsheetID, $range);
            $values = $response->getValues();
            
            Log::info("Sheet data retrieved", [
                'sheet_name' => $sheet_name,
                'total_rows' => count($values ?? [])
            ]);
            
            return !empty($values) ? $values : [];
            
        } catch (\Exception $e) {
            Log::error("Error getting data by sheet name", [
                'sheet_name' => $sheet_name,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get specific values from a row that contains a specific text/section identifier
     * @param string $section_identifier Text to search for in the row
     * @param array $column_indices Array of column indices to extract values from
     * @param int $no_sheet Sheet number (0-based)
     * @return array Array of values from specified columns, or empty array if section not found
     */
    private function get_values_from_section(string $section_identifier, array $column_indices = [], int $no_sheet = 8) {
        try {
            $sheet_data = $this->get_data_by_sheet($no_sheet);
            
            if (empty($sheet_data)) {
                Log::warning("No data found in sheet", ['sheet' => $no_sheet]);
                return [];
            }

            // Search for the row containing the section identifier
            $target_row = null;
            foreach ($sheet_data as $row_index => $row) {
                if (is_array($row)) {
                    foreach ($row as $cell) {
                        if (is_string($cell) && strpos($cell, $section_identifier) !== false) {
                            $target_row = $row;
                            break 2; // Break out of both loops
                        }
                    }
                }
            }

            if ($target_row === null) {
                Log::warning("Section not found", ['section_identifier' => $section_identifier]);
                return [];
            }

            // Extract values from specified column indices
            $extracted_values = [];
            foreach ($column_indices as $col_index) {
                if (isset($target_row[$col_index])) {
                    $value = trim($target_row[$col_index]);
                    $extracted_values[] = $value !== '' ? $value : null;
                } else {
                    $extracted_values[] = null;
                }
            }

            Log::info("Values extracted from section", [
                'section_identifier' => $section_identifier,
                'column_indices' => $column_indices,
                'extracted_values' => $extracted_values
            ]);

            return $extracted_values;
        } catch (\Exception $e) {
            Log::error("Error getting values from section", [
                'error' => $e->getMessage(),
                'section_identifier' => $section_identifier,
                'sheet' => $no_sheet
            ]);
            return [];
        }
    }

    private function convertToInteger($value) {
        // Check if the value is null or empty string, and return null if true
        if ($value === null || trim($value) === "") {
            return null;
        }

        $cleaned = str_replace('.','', $value);

        // Otherwise, cast to integer
        return (int) $cleaned;
    }

    private function convertToDecimal(?string $value): ?float
    {
        if (empty($value)) {
            return null; // Return null if the input is empty
        }

        // Remove all non-numeric characters except comma and dot
        $value = preg_replace('/[^0-9,\.]/', '', $value);

        // If the number contains both dot (.) and comma (,)
        if (strpos($value, '.') !== false && strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value); // Remove thousands separator
            $value = str_replace(',', '.', $value); // Convert decimal separator to dot
        }
        // If only a dot is present (assumed as thousands separator)
        elseif (strpos($value, '.') !== false) {
            $value = str_replace('.', '', $value); // Remove all dots (treat as thousands separators)
        }
        // If only a comma is present (assumed as decimal separator)
        elseif (strpos($value, ',') !== false) {
            $value = str_replace(',', '.', $value); // Convert comma to dot (decimal separator)
        }

        // Ensure the value is numeric before returning
        return is_numeric($value) ? (float) number_format((float) $value, 2, '.', '') : null;
    }

    /**
     * Get count of spatial plannings that can be calculated with new formula
     */
    public function getSpatialPlanningWithCalculationCount(): int
    {
        try {
            // Count spatial plannings that have valid data and are not yet issued (is_terbit = false)
            return SpatialPlanning::where('land_area', '>', 0)
                ->where('site_bcr', '>', 0)
                ->where('is_terbit', false)
                ->count();
        } catch (\Exception $e) {
            Log::error("Error getting spatial planning with calculation count", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get total sum of retribution amounts using new calculation formula
     */
    public function getSpatialPlanningCalculationSum(): float
    {
        try {
            // Get spatial plannings that are not yet issued (is_terbit = false) and have valid data
            $spatialPlannings = SpatialPlanning::where('land_area', '>', 0)
                ->where('site_bcr', '>', 0)
                ->where('is_terbit', false)
                ->get();

            $totalSum = 0;
            foreach ($spatialPlannings as $spatialPlanning) {
                // Use new calculation formula: LUAS LAHAN × BCR × HARGA SATUAN
                $totalSum += $spatialPlanning->calculated_retribution;
            }

            Log::info("Spatial Planning Calculation Sum (is_terbit = false only)", [
                'total_records' => $spatialPlannings->count(),
                'total_sum' => $totalSum,
                'filtered_by' => 'is_terbit = false'
            ]);

            return (float) $totalSum;
        } catch (\Exception $e) {
            Log::error("Error getting spatial planning calculation sum", ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    private function convertToDate($dateString)
    {
        try {
            // Check if the string is empty
            if (empty($dateString)) {
                return null;
            }

            // Try to parse the date string
            $date = Carbon::parse($dateString);

            // Return the Carbon instance
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Return null if an error occurs during parsing
            return null;
        }
    }
}
