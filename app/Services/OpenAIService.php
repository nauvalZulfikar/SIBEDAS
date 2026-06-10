<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        // $this->client = OpenAI::client(env('OPENAI_API_KEY'));
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function generateQueryBasedMainContent($prompt, $mainContent, $chatHistory)
    {
        // Load file JSON
        $jsonPath = public_path('templates/contentTemplatePrompt.json'); // Sesuaikan path
        $jsonData = json_decode(file_get_contents($jsonPath), true);

        // Periksa apakah kategori ada dalam JSON
        if (!isset($jsonData[$mainContent])) {
            return "Template prompt tidak ditemukan.";
        }

        $validationResponse = $this->validatePromptTopic($prompt);
        if ($validationResponse !== 'VALID') {
            return "Prompt yang Anda masukkan tidak relevan dengan data PUPR/SIMBG/DPUTR atau pekerjaan sejenis.";
        }

        // Ambil template berdasarkan kategori
        $promptTemplate = $jsonData[$mainContent]['prompt'];

        // Menyusun pesan untuk OpenAI
        $messages = [
            ['role' => 'system', 'content' => $promptTemplate],
        ];

        // Menambahkan chat history sebagai konteks
        foreach (($chatHistory ?? []) as $chat) {
            if (isset($chat['user'])) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
            }
            if (isset($chat['rawBotResponse'])) {
                $messages[] = ['role' => 'assistant', 'content' => $chat['rawBotResponse']];
            }
        }

        // Tambahkan prompt terbaru user
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Kirim request ke OpenAI API
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    
    // public function generateQueryBasedMainContent($prompt, $mainContent, $chatHistory)
    // {
    //     // Load file JSON
    //     $jsonPath = public_path('templates/contentTemplatePrompt.json'); // Sesuaikan path
    //     $jsonData = json_decode(file_get_contents($jsonPath), true);

    //     // Periksa apakah kategori ada dalam JSON
    //     if (!isset($jsonData[$mainContent])) {
    //         return "Template prompt tidak ditemukan.";
    //     }

    //     // Ambil template berdasarkan kategori
    //     $promptTemplate = $jsonData[$mainContent]['prompt'];

    //     $response = $this->client->chat()->create([
    //         'model' => 'gpt-4o-mini',
    //         'messages' => [
    //             ['role' => 'system', 'content' => $promptTemplate],
    //             ['role' => 'user', 'content' => $prompt],
    //         ],
    //     ]);

    //     return trim($response['choices'][0]['message']['content'] ?? 'No response');
    // }


    public function validateSyntaxQuery($queryResponse)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => "You are a MariaDB SQL expert. Your task is to validate the syntax of an SQL query to ensure it follows proper MariaDB syntax rules. 

                    Guidelines:
                    - Check for any syntax errors, missing keywords, or incorrect clause usage.
                    - Ensure the query is well-structured and adheres to best practices.
                    - Verify that all SQL keywords are used correctly and in the right order.
                    - If the query is valid, respond with: \"VALID\".
                    - If the query has issues, respond with: \"INVALID\".
                    
                    Always respond with either \"VALID\" or \"INVALID\"."
                ],
                ['role' => 'user', 'content' => $queryResponse],
            ],
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function generateNLPFromQuery($inputUser, $resultQuery, $domain = null) {
        $domainContext = '';
        if ($domain === 'dokumen_per_noreg') {
            $domainContext = "

                IMPORTANT - dokumen_per_noreg field interpretation rules:
                - Setiap baris adalah SATU dokumen untuk SATU nomor registrasi.
                - status_kode 0 = Tidak Sesuai (dokumen ditolak/tidak valid)
                - status_kode 1 = Sesuai (dokumen lengkap dan valid)
                - status_kode 3 = Menunggu Verifikasi (belum diperiksa petugas)
                - status_file 'Belum Upload' = file belum diunggah sama sekali
                - keterangan = catatan petugas yang menjelaskan kenapa permohonan terhambat atau apa yang harus diperbaiki. Nilainya sama di setiap baris untuk noreg yang sama — tampilkan SEKALI di awal respons sebagai 'Keterangan Petugas'. Jika NULL berarti belum ada catatan petugas.
                - Jika query kosong (tidak ada hasil), artinya belum ada dokumen yang diupload untuk noreg tersebut.
                - Sebutkan secara eksplisit nama dokumen mana yang bermasalah dan statusnya.
                - Hitung dan sebutkan: berapa total dokumen, berapa yang sesuai, berapa yang tidak sesuai, berapa yang belum upload.";
        }
        if ($domain === 'dokumen_resume') {
            $domainContext = "

                IMPORTANT - dokumen_resume field interpretation rules:
                - business_rab_count = jumlah permohonan USAHA yang dokumen RAB-nya bermasalah (belum/tidak sesuai)
                - business_krk_count = jumlah permohonan USAHA yang dokumen KRK-nya bermasalah
                - business_dlh_count = jumlah permohonan USAHA yang dokumen DLH-nya bermasalah
                - non_business_rab_count = jumlah permohonan NON-USAHA (hunian) yang RAB-nya bermasalah
                - non_business_krk_count = jumlah permohonan NON-USAHA yang KRK-nya bermasalah
                - verified_count = total permohonan yang sudah terverifikasi
                - non_verified_count = total permohonan yang belum terverifikasi (masih dalam proses)
                - Data ini adalah rekapitulasi dashboard, bukan data per nomor registrasi.
                - Jelaskan angka-angka ini dalam konteks kekurangan dokumen per jenis (RAB/KRK/DLH) dan per kategori (usaha/non-usaha).";
        }
        if ($domain === 'pbg_tracking') {
            $domainContext = "

                IMPORTANT - pbg_tracking field interpretation rules:
                - total_dokumen = NULL or 0 means NO documents have been uploaded at all. This is a CRITICAL issue, NOT a sign of completeness.
                - dokumen_tanpa_file > 0 means that many documents are missing files.
                - dokumen_tidak_sesuai > 0 means that many documents were rejected/invalid.
                - status_aplikasi = 'Perbaikan Dokumen' means the applicant MUST resubmit corrected documents.
                - daftar_dokumen_bermasalah lists the specific documents that are problematic.
                - catatan_kekurangan_dokumen = NULL means officers have NOT yet filled in shortage notes, NOT that documents are complete.
                - NULL values in tracking fields (petugas, loket, posisi_berkas) mean data is not yet recorded by officers.
                - Always state clearly when documents are missing or not yet uploaded, do NOT interpret NULL as 'complete' or 'no issues'.";
        }
        if ($domain === 'pbg_status_history') {
            $domainContext = "

                IMPORTANT - pbg_status_history field interpretation rules:
                - Each row is ONE stage in the application's timeline; present the result as a CHRONOLOGICAL TIMELINE (oldest → newest), ideally as a numbered/bulleted list of stages with their tanggal_mulai.
                - status_tahap = stage name; tanggal_mulai = when it started; keterangan = officer note for that stage (show it only when present).
                - status_aplikasi_terkini = the current overall status — state this clearly at the end as 'Posisi/Status terkini'.
                - If status_tahap of the latest row is 'SK PBG Terbit' or 'Sertifikat SLF Terbit', the application is COMPLETE. If it is 'Permohonan Ditolak' or 'Permohonan Dibatalkan', the application was rejected/cancelled — say so explicitly.
                - If the result is empty, say there is no recorded status history for that permohonan yet.
                - Always answer in Bahasa Indonesia and mention the applicant name / registration number when available.";
        }

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Anda adalah asisten ahli berbahasa Indonesia. Tugas Anda adalah menganalisis hasil query database dan mengubahnya menjadi jawaban yang mudah dipahami berdasarkan pertanyaan pengguna.

                    Panduan:
                    - Pahami pertanyaan pengguna dan identifikasi maksud utamanya.
                    - Rangkum atau format hasil query untuk menjawab pertanyaan pengguna secara langsung.
                    - Pastikan respons jelas, ringkas, dan relevan.
                    - Selalu gunakan Bahasa Indonesia dalam setiap respons.
                    - Jika hasil query kosong atau tidak sesuai pertanyaan, berikan respons sopan bahwa data tidak tersedia.

                    Selalu berikan respons yang terstruktur dan masuk akal berdasarkan pertanyaan.$domainContext"
                ],
                ['role' => 'user', 'content' => "User's question: $inputUser \nDatabase result: $resultQuery"],
            ],
        ]);
    
        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function generateFinalText($nlpResult) {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Anda adalah ahli format teks berbahasa Indonesia. Tugas Anda adalah mengambil hasil NLP dan memformatnya menjadi teks terstruktur yang mudah dibaca, cocok untuk ditampilkan di dalam elemen HTML <div>.

                    Panduan:
                    - Pertahankan makna dan kejelasan konten.
                    - Gunakan baris baru yang tepat untuk keterbacaan.
                    - Jika teks mengandung daftar, ubah menjadi poin-poin bullet.
                    - Tekankan kata kunci penting menggunakan tag <strong> jika diperlukan.
                    - Selalu gunakan Bahasa Indonesia dalam output.
                    - Pastikan respons tetap bersih dan ringkas tanpa penjelasan tambahan."
                ],
                ['role' => 'user', 'content' => "Here is the NLP result that needs formatting:\n\n$nlpResult"],
            ],
        ]);
    
        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function classifyMainGenerateText($prompt) {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are an assistant that classifies text into EXACTLY ONE of the following categories:
                    - reklame (ads or product/service promotions)
                    - business_or_industries (business or industries in general)
                    - customers (customers, consumers, or service users)
                    - pbg (tasks related to Building Approval - general info, status, document number; ALSO use this when the user is searching for or asking about a SPECIFIC PERSON by name, e.g. 'cari rida nurhayati', 'siapa ahmad budi', 'data si fulan' — even with possible typos; ALSO use this for anything grouped by or filtered on KECAMATAN / wilayah / sub-district — e.g. 'berapa PBG per kecamatan', 'jumlah permohonan di kecamatan Soreang', 'sebaran PBG per wilayah', 'kecamatan mana paling banyak PBG'. Counting/distribution of applications by area = `pbg`, NOT pbg_status_history.)
                    - retribusi (retributions related to PBG)
                    - spatial_plannings (spatial planning)
                    - tourisms (tourism and tourist destinations)
                    - umkms (Micro, Small, and Medium Enterprises)
                    - pbg_tracking (tracking PBG application progress: missing documents, obstacles/kendala, findings/temuan, PTSP position, loket/helpdesk, officer in charge, document shortage notes, verification status by registration number)
                    - dokumen_per_noreg (detailed document status per registration number: which specific documents are missing, not compliant, or not yet uploaded for a given noreg — use this when user asks about specific registration number's document details)
                    - dokumen_resume (dashboard summary of document deficiencies: RAB/KRK/DLH shortage counts, verified vs non-verified totals, business vs non-business document statistics, overall PBG data summary)
                    - pbg_status_history (the FULL timeline / step-by-step history of an application's status changes — 'posisi permohonan', 'histori permohonan', 'riwayat status', 'sudah sampai mana', 'tahapan proses', 'kapan SK terbit', 'urutan proses dari pengajuan sampai selesai' for a given noreg or applicant name. Use this when the user wants the SEQUENCE of stages over time, not just the single current status.)

                    DEFAULT — If the user is searching by a person's name, applicant name, or business name (e.g. 'cari X', 'siapa X', 'data X', possibly with typos), classify as `pbg`.
                    DEFAULT — If unsure, classify as `pbg`.

                    Respond with ONLY the category snake_case keyword (e.g. `pbg`). No quotes, no explanation, no punctuation."
                ],
                [
                    'role' => 'user',
                    'content' => "Classify the following text:\n\n" . $prompt
                ],
            ],
        ]);

        $raw = trim($response['choices'][0]['message']['content'] ?? '');
        // Normalize: lowercase, strip non-alphanumeric/underscore
        $norm = strtolower(preg_replace('/[^a-z0-9_]/i', '', $raw));

        $valid = [
            'reklame', 'business_or_industries', 'customers',
            'pbg', 'retribusi', 'spatial_plannings',
            'tourisms', 'umkms', 'pbg_tracking',
            'dokumen_resume', 'dokumen_per_noreg', 'pbg_status_history',
        ];

        if (in_array($norm, $valid, true)) {
            return $norm;
        }
        // Substring match (handles 'pbg.' or 'category:pbg')
        foreach ($valid as $cat) {
            if (str_contains($norm, $cat)) {
                return $cat;
            }
        }
        // Fallback — never reject the user.
        return 'pbg';
    }

    public function createMainQuery($classify, $prompt, $chatHistory)
    {
        // Load file JSON
        $jsonPath = public_path('templates/table_config.json');
        $jsonConfig = json_decode(file_get_contents($jsonPath), true);

        // Pastikan kategori tersedia dalam konfigurasi
        if (!isset($jsonConfig[$classify])) {
            return "Error: Kategori tidak ditemukan dalam konfigurasi.";
        }

        // Ambil nama tabel dan kolom
        $tableName = $jsonConfig[$classify]['table_name'];
        $columns = implode(', ', $jsonConfig[$classify]['list_column']);

        // Extra context per domain
        $extraContext = '';
        if ($classify === 'dokumen_per_noreg') {
            $extraContext = "
                Important column semantics for dokumen_per_noreg (v_pbg_document_status view):
                - Each row = one document for one registration number
                - status_kode: 0=Tidak Sesuai (rejected/invalid), 1=Sesuai (compliant), 3=Menunggu Verifikasi (awaiting review)
                - status_file: 'Belum Upload' means no file attached, 'Sudah Upload' means file exists
                - kategori_dokumen: document category name (Data Umum, Data Teknis Arsitektur, Data Teknis Struktur, Data Teknis MEP)
                - keterangan: officer note from pbg_statuses explaining why the application is blocked or what needs to be fixed

                CRITICAL SQL RULES — you MUST follow these exactly:
                1. NEVER use SELECT * — always list columns explicitly.
                2. NEVER write two separate SELECT statements — write exactly ONE SELECT query.
                3. keterangan is a normal column — include it in the same SELECT list as all other columns, e.g.:
                   SELECT registration_number, nama_pemilik, status_aplikasi, alamat, keterangan, nama_dokumen, kategori_dokumen, status_dokumen, status_kode, catatan_dokumen, status_file, tgl_upload, tgl_update FROM v_pbg_document_status WHERE ...
                4. To find all docs for a specific noreg: WHERE registration_number = 'X' — use LIMIT 50 (not 10).
                5. To find missing/problematic docs: WHERE registration_number = 'X' AND (status_kode != 1 OR status_file = 'Belum Upload').";
        }
        if ($classify === 'dokumen_resume') {
            $extraContext = "
                Important column semantics for dokumen_resume (bigdata_resumes table):
                - business_rab_count: number of business (usaha) applications with RAB document issues (status != 1)
                - business_krk_count: number of business applications with KRK document issues
                - business_dlh_count: number of business applications with DLH document issues
                - non_business_rab_count: number of non-business (non-usaha/hunian) applications with RAB issues
                - non_business_krk_count: number of non-business applications with KRK issues
                - verified_count: total applications that have been verified/approved
                - non_verified_count: total applications still pending verification (Verifikasi Kelengkapan or Perbaikan Dokumen)
                - potention_count: total potential applications in the system
                - business_count: total business-type applications
                - non_business_count: total non-business-type applications
                - resume_type: filter type (simbg or leader)
                Use ORDER BY updated_at DESC LIMIT 1 to get the latest data.";
        }
        if ($classify === 'pbg_tracking') {
            $extraContext = "
                Important column semantics for pbg_tracking:
                - total_dokumen = NULL means NO documents have been uploaded at all (critical issue)
                - total_dokumen > 0 means documents exist
                - dokumen_tanpa_file = number of document slots with no file uploaded
                - dokumen_tidak_sesuai = number of documents marked as invalid/rejected
                - dokumen_menunggu_verifikasi = number of documents awaiting officer review
                - daftar_dokumen_bermasalah = pipe-separated list of problematic document names
                - status_aplikasi = 'Perbaikan Dokumen' means the applicant must fix/resubmit documents
                - catatan_kekurangan_dokumen and catatan_kekurangan_dokumen_payments contain officer notes on missing docs
                NULL values in tracking fields mean the data has not been filled by officers yet, NOT that everything is complete.";
        }
        if ($classify === 'pbg_status_history') {
            $extraContext = "
                Important column semantics for pbg_status_history (v_pbg_status_history view):
                - Each row = ONE status transition in an application's timeline. Many rows for the same registration_number = the full chronological history.
                - status_tahap = the human-readable stage name (e.g. 'Verifikasi Kelengkapan Dokumen', 'Perbaikan Dokumen', 'Perhitungan Retribusi', 'SK PBG Terbit', 'Permohonan Ditolak').
                - status_kode = the numeric status code behind status_tahap.
                - tanggal_mulai = when that stage started; tanggal_selesai = its due/finish date (may be NULL).
                - keterangan = officer note for that stage (NULL = no note).
                - status_aplikasi_terkini = the application's latest/current overall status (same value on every row for that noreg).
                CRITICAL SQL RULES — follow exactly:
                1. NEVER use SELECT * — list columns explicitly.
                2. To show a timeline for one applicant/noreg, ALWAYS add ORDER BY tanggal_mulai ASC, id ASC so stages read oldest → newest.
                3. To get the timeline of a specific noreg: WHERE registration_number = 'X' ORDER BY tanggal_mulai ASC, id ASC LIMIT 50.
                4. Do NOT add LIMIT 10 for a single-applicant timeline (it truncates the history) — use LIMIT 50.
                5. When the user names a person, filter on nama_pemilik with the fuzzy SOUNDEX expansion described below.";
        }

        if ($classify === 'pbg') {
            $extraContext = "
                Important column semantics for pbg (pbg_task table):
                - kecamatan = the sub-district (one of the 31 official Kabupaten Bandung kecamatan, Title-Case, e.g. 'Soreang', 'Baleendah', 'Ciparay'). Populated for almost every row; only a handful are NULL.
                - kecamatan_source = how it was derived: 'pip' (point-in-polygon on coordinates, authoritative) or 'address' (parsed from the address string). NULL when unresolved. Do NOT show kecamatan_source to the user unless they explicitly ask about data provenance.
                KECAMATAN SQL RULES — follow exactly:
                1. To count/aggregate applications per kecamatan: SELECT kecamatan, COUNT(*) AS total FROM pbg_task WHERE kecamatan IS NOT NULL GROUP BY kecamatan ORDER BY total DESC.
                2. To filter by a specific kecamatan, match exactly and case-insensitively: WHERE kecamatan = 'Soreang' (the stored values are already canonical Title-Case; never use the leaky old address text).
                3. When the user names a kecamatan that might be typo'd, also allow LIKE: WHERE kecamatan LIKE 'Soreang%'.
                4. kecamatan is a plain column on pbg_task — never JOIN another table to get it.";
        }

        // Konversi chatHistory ke dalam format messages
        $messages = [
            [
                'role' => 'system',
                'content' => "You are an AI assistant that generates only valid MariaDB queries based on user requests.
                Use the following table information to construct the SQL query:

                - Table Name: $tableName
                - Available Columns: $columns
                $extraContext

                FUZZY NAME MATCHING (MANDATORY) — Indonesian person/owner/business names are very often typo'd
                (e.g. 'suhairi' vs 'suhaeri', 'rahmat' vs 'rohmat'). Whenever the user is searching by a
                person's name, owner name, applicant name, business name, or any free-text identifier
                (typical columns: name, owner_name, nama_pemilik, nama_pemohon, nama_usaha, business_name),
                you MUST expand the WHERE clause to also match phonetically via SOUNDEX.

                Pattern — instead of:
                    WHERE owner_name LIKE '%iri suhairi%'
                you MUST write:
                    WHERE (
                        owner_name LIKE '%iri suhairi%'
                        OR SOUNDEX(owner_name) = SOUNDEX('iri suhairi')
                        OR SOUNDEX(REPLACE(owner_name, ' ', '')) = SOUNDEX(REPLACE('iri suhairi', ' ', ''))
                    )

                Apply the same OR-SOUNDEX expansion to every name-like column referenced in the search.
                Never use SOUNDEX on dates, numbers, IDs, or registration numbers — only on name/text fields.

                Generate only the SQL query without any explanation or additional text.
                The query should include `LIMIT 10` to restrict the results."
            ]
        ];

        // Menambahkan chat history sebagai konteks
        foreach (($chatHistory ?? []) as $chat) {
            if (isset($chat['user'])) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
            }
            if (isset($chat['rawBotResponse'])) {
                $messages[] = ['role' => 'assistant', 'content' => $chat['rawBotResponse']];
            }
        }

        // Tambahkan prompt utama pengguna
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Kirim permintaan ke model AI
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    
    // public function createMainQuery($classify, $prompt)
    // {
    //     // Load file JSON
    //     $jsonPath = public_path('templates/table_config.json');
    //     $jsonConfig = json_decode(file_get_contents($jsonPath), true);

    //     // Pastikan kategori tersedia dalam konfigurasi
    //     if (!isset($jsonConfig[$classify])) {
    //         return "Error: Kategori tidak ditemukan dalam konfigurasi.";
    //     }

    //     // Ambil nama tabel dan kolom
    //     $tableName = $jsonConfig[$classify]['table_name'];
    //     $columns = implode(', ', $jsonConfig[$classify]['list_column']);

    //     $response = $this->client->chat()->create([
    //         'model' => 'gpt-4o-mini',
    //         'messages' => [
    //             [
    //                 'role' => 'system',
    //                 'content' => "You are an AI assistant that generates only valid MariaDB queries based on user requests. 
    //                 Use the following table information to construct the SQL query:

    //                 - Table Name: $tableName
    //                 - Available Columns: $columns

    //                 Generate only the SQL query without any explanation or additional text
    //                 The query should include `LIMIT 10` to restrict the results."
    //             ],
    //             [
    //                 'role' => 'user',
    //                 'content' => $prompt
    //             ],
    //         ],
    //     ]);
    
    //     return trim($response['choices'][0]['message']['content'] ?? 'No response');
    // }

    public function validatePromptTopic($prompt)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => "You are a classification expert. Determine if the user's request is related to the Indonesian Ministry of Public Works and Public Housing (PUPR), DPUTR, SIMBG, or construction-related tasks managed by these institutions. 
            
    Only respond with:
    - VALID → if it's relevant to those topics
    - INVALID → if not related at all"
            ],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'INVALID');
    }
}
