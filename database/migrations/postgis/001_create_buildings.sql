-- PostGIS schema for vector-tile polygon layer.
-- Applied via `php artisan postgis:migrate` (idempotent — tracked in
-- postgis_migrations table). Manual fallback for dev:
--   docker compose exec postgis psql -U sibedas_spatial -d sibedas_spatial \
--     -f /docker-entrypoint-initdb.d/001_create_buildings.sql

-- PostGIS extension is bundled with the image but the CREATE is harmless
-- if re-run.
CREATE EXTENSION IF NOT EXISTS postgis;

-- Mirrors MySQL.detected_buildings primary key (BIGINT). Polygons synced
-- via app/Console/Commands/SyncBuildingsToPostgis (Phase 3).
CREATE TABLE IF NOT EXISTS buildings (
    id                    BIGINT       PRIMARY KEY,
    geom                  GEOMETRY(Polygon, 4326)  NOT NULL,
    centroid              GEOMETRY(Point,   4326)  NOT NULL,
    source                VARCHAR(50),
    verification_status   VARCHAR(30),
    district              VARCHAR(100),
    ward                  VARCHAR(100),
    matched_pbg_task_id   BIGINT,
    area_m2               NUMERIC(10, 2),
    status_color          VARCHAR(7),   -- precomputed hex like '#ef4444'
    updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

-- Spatial index (the whole point of this table). GIST is the standard for
-- polygons; the planner uses it for ST_Within / && bbox / ST_Intersects.
CREATE INDEX IF NOT EXISTS idx_buildings_geom     ON buildings USING GIST (geom);
CREATE INDEX IF NOT EXISTS idx_buildings_centroid ON buildings USING GIST (centroid);

-- Btree indexes for filter columns used by the tile function (Phase 7).
CREATE INDEX IF NOT EXISTS idx_buildings_verif     ON buildings (verification_status);
CREATE INDEX IF NOT EXISTS idx_buildings_district  ON buildings (district);
CREATE INDEX IF NOT EXISTS idx_buildings_pbg_task  ON buildings (matched_pbg_task_id);
CREATE INDEX IF NOT EXISTS idx_buildings_source    ON buildings (source);
