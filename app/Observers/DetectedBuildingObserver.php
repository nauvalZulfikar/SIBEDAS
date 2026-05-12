<?php

namespace App\Observers;

use App\Jobs\InvalidateBuildingTiles;
use App\Models\DetectedBuilding;

/**
 * Phase 15 — when verification_status changes on a building, fire a
 * tile-cache invalidation so the next request from any client sees the
 * new colour. The job is dispatched async so the PUT endpoint stays
 * fast; the tile is at most a few seconds stale until the queue worker
 * picks it up.
 */
class DetectedBuildingObserver
{
    public function updated(DetectedBuilding $building): void
    {
        // Only the verification colour drives status_color on the tile, so
        // skip the dispatch for purely structural edits (geom backfills,
        // district enrichment) — those flow through the daily sync.
        if (!$building->wasChanged('verification_status')) return;

        if ($building->latitude === null || $building->longitude === null) return;

        InvalidateBuildingTiles::dispatch(
            (float) $building->latitude,
            (float) $building->longitude,
        );
    }
}
