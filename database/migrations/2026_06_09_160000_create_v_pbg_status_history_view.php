<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * v_pbg_status_history — full status timeline per permohonan, exposing the
     * same data the "Cek Posisi Permohonan" modal shows, but queryable by the
     * Neng Bedas chatbot (joined to pbg_task so it can be filtered by
     * registration_number / owner name, not just the opaque pbg_task_uuid).
     *
     * One row = one status transition. Multiple rows per noreg = the timeline.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_pbg_status_history AS
            SELECT
                ps.id,
                pt.uuid                  AS pbg_task_uuid,
                pt.registration_number,
                pt.document_number,
                pt.name                  AS nama_bangunan,
                pt.owner_name            AS nama_pemilik,
                pt.address               AS alamat,
                pt.status_name           AS status_aplikasi_terkini,
                ps.status                AS status_kode,
                ps.status_name           AS status_tahap,
                COALESCE(ps.data_created_at, ps.due_date, ps.created_at) AS tanggal_mulai,
                ps.due_date              AS tanggal_selesai,
                ps.note                  AS keterangan,
                ps.uid                   AS petugas_uid
            FROM pbg_statuses ps
            JOIN pbg_task pt ON pt.uuid = ps.pbg_task_uuid
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_pbg_status_history");
    }
};
