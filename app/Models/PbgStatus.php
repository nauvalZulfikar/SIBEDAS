<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PbgStatus extends Model
{
    protected $table = 'pbg_statuses';

    protected $fillable = [
        'pbg_task_uuid',
        'status',
        'status_name',
        'slf_status',
        'slf_status_name',
        'due_date',
        'uid',
        'note',
        'file',
        'data_due_date',
        'data_created_at',
        'slf_data',
    ];

    public function pbgTask()
    {
        return $this->belongsTo(PbgTask::class, 'pbg_task_uuid', 'uuid');
    }

    public static function createOrUpdateFromApi(array $apiResponse, string $pbgTaskUuid)
    {
        $data = $apiResponse['data'] ?? [];
        $note = $data['note'] ?? null;

        $row = self::updateOrCreate(
            [
                'pbg_task_uuid' => $pbgTaskUuid,
                'status'        => $apiResponse['status'], // key pencarian unik
            ],
            [
                'status_name'   => $apiResponse['status_name'] ?? null,
                'slf_status'    => $apiResponse['slf_status'] ?? null,
                'slf_status_name' => $apiResponse['slf_status_name'] ?? null,
                'due_date'      => $apiResponse['due_date'] ?? null,

                // nested data
                'uid'           => $data['uid'] ?? null,
                'note'          => $note,
                'file'          => $data['file'] ?? null,
                'data_due_date' => $data['due_date'] ?? null,
                'data_created_at' => isset($data['created_at']) ? Carbon::parse($data['created_at'])->format('Y-m-d H:i:s') : null,

                'slf_data'      => $apiResponse['slf_data'] ?? null,
            ]
        );

        // SIMBG attaches the verifier's note to the step that produced it
        // (e.g., "Verifikasi Kelengkapan Dokumen"), not to the resulting
        // transition (e.g., "Perbaikan Dokumen"). The SIMBG API we consume
        // only echoes the current status + the active note, so the note
        // arrives bound to the wrong row. Backfill it onto the most recent
        // prior status row for this task when that row's note is still empty
        // so non-UI consumers (Excel export, OpenAI service, etc.) see the
        // catatan where SIMBG shows it.
        if (!empty($note)) {
            self::where('pbg_task_uuid', $pbgTaskUuid)
                ->where('id', '<', $row->id)
                ->where(function ($q) {
                    $q->whereNull('note')->orWhere('note', '');
                })
                ->orderByDesc('id')
                ->limit(1)
                ->update(['note' => $note, 'updated_at' => now()]);
        }

        return $row;
    }
}
