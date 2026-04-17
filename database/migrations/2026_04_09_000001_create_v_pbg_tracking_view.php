<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_pbg_tracking AS
            SELECT
                pt.registration_number,
                pt.name AS nama_bangunan,
                pt.owner_name AS nama_pemilik,
                pt.address AS alamat_bangunan,
                pt.status_name AS status_aplikasi,
                pt.slf_status_name AS status_slf,
                pt.application_type_name AS jenis_permohonan,
                pt.function_type AS fungsi_bangunan,
                pt.due_date AS batas_waktu,

                gs.status_verifikasi,
                gs.status_permohonan,
                gs.catatan_kekurangan_dokumen,
                gs.keterangan,
                gs.temuan,
                gs.ptsp AS posisi_berkas_ptsp,
                gs.helpdesk AS loket_helpdesk,
                gs.pj AS penanggung_jawab,
                gs.tanggal_catatan,
                gs.krk_kkpr,
                gs.no_krk,
                gs.lh,
                gs.ska,
                gs.dok_tanah,

                pay.document_shortage_note AS catatan_kekurangan_dokumen_payments,
                pay.remarks AS keterangan_payments,
                pay.ptsp_status AS status_ptsp,
                pay.helpdesk AS loket_payments,
                pay.person_in_charge AS petugas,
                pay.pbg_operator AS operator_pbg,
                pay.verification_status AS status_verifikasi_payments,
                pay.application_status AS status_aplikasi_payments,
                pay.agency_validation AS validasi_dinas,
                pay.taru_potential AS potensi_taru,
                pay.retribution_category AS kategori_retribusi,
                pay.penalty_amount AS denda,
                pay.note_date_raw AS tanggal_catatan_payments,

                doc_summary.total_dokumen,
                doc_summary.dokumen_tidak_sesuai,
                doc_summary.dokumen_tanpa_file,
                doc_summary.dokumen_menunggu_verifikasi,
                doc_summary.daftar_dokumen_bermasalah

            FROM pbg_task pt
            LEFT JOIN pbg_task_google_sheet gs
                ON gs.no_registrasi = pt.registration_number
            LEFT JOIN pbg_task_payments pay
                ON pay.source_registration_number = pt.registration_number
            LEFT JOIN (
                SELECT
                    pbg_task_uuid,
                    COUNT(*) AS total_dokumen,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS dokumen_tidak_sesuai,
                    SUM(CASE WHEN file IS NULL OR file = '' THEN 1 ELSE 0 END) AS dokumen_tanpa_file,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS dokumen_menunggu_verifikasi,
                    GROUP_CONCAT(
                        CASE
                            WHEN status = 0 THEN CONCAT(name, ' [Tidak Sesuai]')
                            WHEN file IS NULL OR file = '' THEN CONCAT(name, ' [Belum Upload]')
                            ELSE NULL
                        END
                        ORDER BY data_type_name
                        SEPARATOR ' | '
                    ) AS daftar_dokumen_bermasalah
                FROM pbg_task_detail_data_lists
                GROUP BY pbg_task_uuid
            ) doc_summary ON doc_summary.pbg_task_uuid = pt.uuid
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_pbg_tracking');
    }
};
