-- Phase 7: tile function consumed by Martin.
--
-- Martin auto-publishes any function with the signature
--     (z integer, x integer, y integer, query_params json) RETURNS bytea
-- as a custom tile source, reachable at:
--     /building_tile/{z}/{x}/{y}?district=…&status=…&source=…
--
-- query_params is the URL querystring converted to JSON by Martin, so the
-- function reads it via json operators. Keys we honour:
--     district -> string (matches buildings.district exactly)
--     status   -> string (matches buildings.verification_status)
--     source   -> string (matches buildings.source)
--
-- Output: MVT bytes with a single layer named 'buildings', emitting only
-- the properties Leaflet needs (id, status_color, area_class, source).
-- Returning a narrow property set keeps tiles small and avoids leaking
-- PII (district / ward only flow through the WHERE filter).

CREATE OR REPLACE FUNCTION building_tile(
    z            integer,
    x            integer,
    y            integer,
    query_params json DEFAULT '{}'::json
)
RETURNS bytea AS $$
DECLARE
    mvt              bytea;
    f_district       text := NULLIF(query_params->>'district', '');
    f_status         text := NULLIF(query_params->>'status', '');
    f_source         text := NULLIF(query_params->>'source', '');
    f_exclude_source text := NULLIF(query_params->>'exclude_source', '');
    f_min_area       numeric := NULLIF(query_params->>'min_area', '')::numeric;
BEGIN
    -- buildings.geom is stored in 4326; ST_TileEnvelope returns 3857.
    -- Project the envelope back to 4326 for the && index probe so the
    -- GIST index on buildings(geom) can be used. Then project geom INTO
    -- 3857 for ST_AsMVTGeom (it requires source and envelope in matching
    -- SRIDs).
    SELECT INTO mvt ST_AsMVT(tile.*, 'buildings', 4096, 'geom')
    FROM (
        SELECT
            b.id,
            b.status_color,
            b.source,
            CASE
                WHEN b.area_m2 IS NULL THEN 'unknown'
                WHEN b.area_m2 <  50    THEN 'tiny'
                WHEN b.area_m2 <  200   THEN 'small'
                WHEN b.area_m2 < 1000   THEN 'medium'
                ELSE                          'large'
            END AS area_class,
            ST_AsMVTGeom(
                ST_Transform(b.geom, 3857),
                ST_TileEnvelope(z, x, y),
                4096, 64, true
            ) AS geom
        FROM buildings b
        WHERE b.geom && ST_Transform(ST_TileEnvelope(z, x, y, margin => (64.0 / 4096)), 4326)
          AND (f_district       IS NULL OR b.district = f_district)
          AND (f_status         IS NULL OR b.verification_status = f_status)
          AND (f_source         IS NULL OR b.source = f_source)
          AND (f_exclude_source IS NULL OR b.source <> f_exclude_source)
          AND (f_min_area       IS NULL OR b.area_m2 >= f_min_area)
        LIMIT 50000
    ) AS tile
    WHERE tile.geom IS NOT NULL;

    RETURN mvt;
END;
$$ LANGUAGE plpgsql STABLE PARALLEL SAFE;

COMMENT ON FUNCTION building_tile(integer, integer, integer, json) IS
    'Phase 7 vector-tile producer for /satellite-monitoring polygon layer. '
    'Filters: district, status, source, min_area. Properties emitted: '
    'id, status_color, source, area_class.';
