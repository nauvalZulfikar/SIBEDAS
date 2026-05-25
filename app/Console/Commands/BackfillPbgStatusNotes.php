<?php

namespace App\Console\Commands;

use App\Models\PbgStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off backfill: copy the verifier's note from later status rows
 * (typically "Perbaikan Dokumen") back to the earlier status row that
 * actually produced it (typically "Verifikasi Kelengkapan Dokumen"),
 * matching how SIMBG displays the catatan.
 *
 * The sync logic in PbgStatus::createOrUpdateFromApi was naively storing
 * the incoming note against whatever the current SIMBG status code was,
 * leaving the prior Verifikasi row with a null note. After we fixed the
 * sync to backfill on write, this command applies the same correction
 * to rows that were already imported the old way.
 *
 * Safe to re-run: only writes when the target row's note is still
 * null/empty and a later sibling actually has a non-empty note.
 */
class BackfillPbgStatusNotes extends Command
{
    protected $signature = 'pbg:backfill-status-notes {--dry-run : Show what would change without writing}';

    protected $description = 'Copy verifier notes from later status rows back to the prior empty row per task';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Find each affected pair: earliest-id row with empty note that
        // has a later sibling carrying a real note.
        $pairs = DB::table('pbg_statuses as s1')
            ->select(
                's1.id as target_id',
                's1.pbg_task_uuid',
                's1.status_name as target_status',
                's2.id as source_id',
                's2.status_name as source_status',
                's2.note as source_note'
            )
            ->joinSub(
                DB::table('pbg_statuses')
                    ->select('pbg_task_uuid', DB::raw('MIN(id) as min_id'))
                    ->groupBy('pbg_task_uuid'),
                'mins',
                fn ($j) => $j->on('mins.pbg_task_uuid', '=', 's1.pbg_task_uuid')
                    ->on('mins.min_id', '=', 's1.id')
            )
            ->join('pbg_statuses as s2', function ($j) {
                $j->on('s2.pbg_task_uuid', '=', 's1.pbg_task_uuid')
                    ->whereColumn('s2.id', '>', 's1.id');
            })
            ->where(function ($q) {
                $q->whereNull('s1.note')->orWhere('s1.note', '');
            })
            ->whereNotNull('s2.note')
            ->where('s2.note', '!=', '')
            ->orderBy('s1.id')
            ->get();

        if ($pairs->isEmpty()) {
            $this->info('Nothing to backfill — all status rows already aligned.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Found {$pairs->count()} status row(s) to backfill.");

        $seen = [];
        $updated = 0;

        foreach ($pairs as $p) {
            // One task may have multiple later sibling rows with notes;
            // only fill the earliest row once with the chronologically
            // first non-empty note we encountered.
            if (isset($seen[$p->target_id])) {
                continue;
            }
            $seen[$p->target_id] = true;

            $preview = mb_substr((string) $p->source_note, 0, 80);
            $this->line(sprintf(
                '  task=%s  %s(#%d) <- %s(#%d) "%s%s"',
                $p->pbg_task_uuid,
                $p->target_status ?: '?',
                $p->target_id,
                $p->source_status ?: '?',
                $p->source_id,
                $preview,
                mb_strlen($p->source_note) > 80 ? '...' : ''
            ));

            if (!$dryRun) {
                PbgStatus::where('id', $p->target_id)->update([
                    'note' => $p->source_note,
                    'updated_at' => now(),
                ]);
                $updated++;
            }
        }

        if ($dryRun) {
            $this->info("[dry-run] {$pairs->count()} row(s) eligible. Re-run without --dry-run to apply.");
        } else {
            $this->info("Backfilled {$updated} row(s).");
        }

        return self::SUCCESS;
    }
}
