<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbgTask extends Model
{
    use HasFactory;
    protected $table = 'pbg_task';
    protected $fillable = [
        'uuid',
        'name',
        'owner_name',
        'application_type',
        'application_type_name',
        'condition',
        'registration_number',
        'document_number',
        'address',
        'status',
        'status_name',
        'slf_status',
        'slf_status_name',
        'function_type',
        'consultation_type',
        'due_date',
        'start_date',
        'retribution',
        'total_area',
        'unit',
        'land_certificate_phase',
        'task_created_at',
        'is_valid'
    ];

    public function pbg_task_retributions(){
        return $this->hasOne(PbgTaskRetributions::class, 'pbg_task_uid', 'uuid');
    }

    public function pbg_task_index_integrations(){
        return $this->hasOne(PbgTaskIndexIntegrations::class, 'pbg_task_uid', 'uuid');
    }

    public function pbg_task_detail(){
        return $this->hasOne(PbgTaskDetail::class, 'pbg_task_uid', 'uuid');
    }

    public function googleSheet(){
        return $this->hasOne(PbgTaskGoogleSheet::class, 'formatted_registration_number', 'registration_number');
    }

    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, 'pbg_task_uid', 'uuid');
    }

    public function attachments(){
        return $this->hasMany(PbgTaskAttachment::class, 'pbg_task_id', 'id');
    }

    /**
     * Get the data lists associated with this PBG task (One to Many)
     * One pbg_task can have many data lists
     */
    public function dataLists()
    {
        return $this->hasMany(PbgTaskDetailDataList::class, 'pbg_task_uuid', 'uuid');
    }

    public function pbg_status()
    {
        return $this->hasOne(PbgStatus::class, 'pbg_task_uuid', 'uuid');
    }

    /**
     * Get only data lists with files
     */
    public function dataListsWithFiles()
    {
        return $this->hasMany(PbgTaskDetailDataList::class, 'pbg_task_uuid', 'uuid')
                    ->whereNotNull('file')
                    ->where('file', '!=', '');
    }

    /**
     * Get data lists by status
     */
    public function dataListsByStatus($status)
    {
        return $this->hasMany(PbgTaskDetailDataList::class, 'pbg_task_uuid', 'uuid')
                    ->where('status', $status);
    }

    /**
     * Get data lists by data type
     */
    public function dataListsByType($dataType)
    {
        return $this->hasMany(PbgTaskDetailDataList::class, 'pbg_task_uuid', 'uuid')
                    ->where('data_type', $dataType);
    }

    /**
     * Create or update data lists from API response
     */
    public function syncDataLists(array $dataLists): void
    {
        foreach ($dataLists as $listData) {
            PbgTaskDetailDataList::updateOrCreate(
                ['uid' => $listData['uid']],
                [
                    'name' => $listData['name'] ?? null,
                    'description' => $listData['description'] ?? null,
                    'status' => $listData['status'] ?? null,
                    'status_name' => $listData['status_name'] ?? null,
                    'data_type' => $listData['data_type'] ?? null,
                    'data_type_name' => $listData['data_type_name'] ?? null,
                    'file' => $listData['file'] ?? null,
                    'note' => $listData['note'] ?? null,
                    'pbg_task_uuid' => $this->uuid,
                ]
            );
        }
    }

    /**
     * Get data lists count by status
     */
    public function getDataListsCountByStatusAttribute()
    {
        return $this->dataLists()
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');
    }

    /**
     * Get data lists count by data type
     */
    public function getDataListsCountByTypeAttribute()
    {
        return $this->dataLists()
                    ->selectRaw('data_type, COUNT(*) as count')
                    ->groupBy('data_type')
                    ->pluck('count', 'data_type');
    }
}
