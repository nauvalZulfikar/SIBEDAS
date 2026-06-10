<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repoint v_property_master from the leaky vendor `kecamatan` to the
 * authoritative `kecamatan_verified` (point-in-polygon, BATAS_KECAMATAN_DESA).
 *
 * The old WHERE gate `db.kecamatan IS NOT NULL` was wrong in BOTH directions:
 *   - INCLUDED 579,083 buildings actually outside Kab Bandung (vendor-mislabeled)
 *     → Google Places enrichment leaked ~20k paid calls onto out-of-region buildings.
 *   - EXCLUDED 695,543 genuine in-region buildings whose vendor `kecamatan` was NULL.
 *
 * New gate `db.kecamatan_verified IS NOT NULL` yields exactly the 1,209,224
 * verified-in-region buildings. The `kecamatan` column is kept (old value, for the
 * building-detail popup) and `kecamatan_verified` is added for correct targeting.
 *
 * Consumers: EnrichGooglePlaces (selector — now leak-free) and
 * DetectedBuildingController::info (single-building popup, only ever called for
 * verified-in-region buildings shown on the scoped map).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement($this->viewSql('db.kecamatan_verified is not null'));
    }

    public function down(): void
    {
        // Restore the previous (leaky) gate; drop the added column is implicit on replace.
        DB::statement($this->viewSqlLegacy());
    }

    private function viewSql(string $whereGate): string
    {
        return "CREATE OR REPLACE VIEW v_property_master AS
            select
                db.id AS id,
                db.latitude AS latitude,
                db.longitude AS longitude,
                db.estimated_area_m2 AS estimated_area_m2,
                db.kecamatan AS kecamatan,
                db.kecamatan_verified AS kecamatan_verified,
                db.building_district_name AS kelurahan,
                db.detection_source AS detection_source,
                db.verification_status AS verification_status,
                (case
                    when pt.status = 20 then 'Berizin'
                    when (pt.status is not null and pt.status not in (3,9,20,22)) then 'Proses'
                    when pt.status in (3,9,22) then 'Ditolak'
                    else 'Tidak Berizin' end) AS status_izin,
                pt.owner_name AS owner_name,
                pt.registration_number AS registration_number,
                pt.status_name AS pbg_status_name,
                pt.function_type AS function_type,
                ptd.name_building AS name_building,
                ptd.building_address AS building_address,
                ptd.total_area AS pbg_total_area,
                ptd.building_type_name AS building_type_name,
                coalesce(ptd.total_area, db.estimated_area_m2) AS best_area,
                (case when ptd.total_area is not null then 'dokumen' else 'satelit' end) AS area_source,
                (case
                    when pt.status = 20 then 0
                    when coalesce(ptd.total_area, db.estimated_area_m2) is null then 0
                    when coalesce(ptd.total_area, db.estimated_area_m2) >= 100
                        then round((((((coalesce(ptd.total_area, db.estimated_area_m2) * 0.0040) * 7035000) * floor(((0.17 * 1.5) * 10000))) / 10000) * 1.5), 0)
                    else round((((((coalesce(ptd.total_area, db.estimated_area_m2) * 0.0040) * 7035000) * floor(((0.15 * 1.2) * 10000))) / 10000) * 1.5), 0)
                end) AS potensi_retribusi_rp
            from ((detected_buildings db
                left join pbg_task pt on (pt.id = db.matched_pbg_task_id))
                left join pbg_task_details ptd on (ptd.pbg_task_uid = pt.uuid))
            where ({$whereGate})";
    }

    private function viewSqlLegacy(): string
    {
        // Pre-fix view: gated on the old leaky column, no kecamatan_verified exposed.
        return "CREATE OR REPLACE VIEW v_property_master AS
            select
                db.id AS id,
                db.latitude AS latitude,
                db.longitude AS longitude,
                db.estimated_area_m2 AS estimated_area_m2,
                db.kecamatan AS kecamatan,
                db.building_district_name AS kelurahan,
                db.detection_source AS detection_source,
                db.verification_status AS verification_status,
                (case
                    when pt.status = 20 then 'Berizin'
                    when (pt.status is not null and pt.status not in (3,9,20,22)) then 'Proses'
                    when pt.status in (3,9,22) then 'Ditolak'
                    else 'Tidak Berizin' end) AS status_izin,
                pt.owner_name AS owner_name,
                pt.registration_number AS registration_number,
                pt.status_name AS pbg_status_name,
                pt.function_type AS function_type,
                ptd.name_building AS name_building,
                ptd.building_address AS building_address,
                ptd.total_area AS pbg_total_area,
                ptd.building_type_name AS building_type_name,
                coalesce(ptd.total_area, db.estimated_area_m2) AS best_area,
                (case when ptd.total_area is not null then 'dokumen' else 'satelit' end) AS area_source,
                (case
                    when pt.status = 20 then 0
                    when coalesce(ptd.total_area, db.estimated_area_m2) is null then 0
                    when coalesce(ptd.total_area, db.estimated_area_m2) >= 100
                        then round((((((coalesce(ptd.total_area, db.estimated_area_m2) * 0.0040) * 7035000) * floor(((0.17 * 1.5) * 10000))) / 10000) * 1.5), 0)
                    else round((((((coalesce(ptd.total_area, db.estimated_area_m2) * 0.0040) * 7035000) * floor(((0.15 * 1.2) * 10000))) / 10000) * 1.5), 0)
                end) AS potensi_retribusi_rp
            from ((detected_buildings db
                left join pbg_task pt on (pt.id = db.matched_pbg_task_id))
                left join pbg_task_details ptd on (ptd.pbg_task_uid = pt.uuid))
            where (db.kecamatan is not null)";
    }
};
