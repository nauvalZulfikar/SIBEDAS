<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbgTaskDetailDataList extends Model
{
    use HasFactory;

    protected $table = 'pbg_task_detail_data_lists';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'status',
        'status_name',
        'data_type',
        'data_type_name',
        'file',
        'note',
        'pbg_task_uuid',
    ];

    protected $casts = [
        'status' => 'integer',
        'data_type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to PbgTask (Many to One)
     * Many data lists belong to one pbg_task
     */
    public function pbgTask()
    {
        return $this->belongsTo(PbgTask::class, 'pbg_task_uuid', 'uuid');
    }

    /**
     * Get the full file path
     */
    public function getFilePathAttribute()
    {
        return $this->file ? storage_path('app/public/' . $this->file) : null;
    }

    /**
     * Get the file URL
     */
    public function getFileUrlAttribute()
    {
        return $this->file ? asset('storage/' . $this->file) : null;
    }

    /**
     * Check if file exists
     */
    public function hasFile()
    {
        return !empty($this->file) && file_exists($this->getFilePathAttribute());
    }

    /**
     * Get status badge color based on status
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            1 => 'success', // Sesuai
            0 => 'danger',  // Tidak Sesuai
            default => 'secondary'
        };
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by data type
     */
    public function scopeByDataType($query, $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    /**
     * Scope: With files only
     */
    public function scopeWithFiles($query)
    {
        return $query->whereNotNull('file')->where('file', '!=', '');
    }

    /**
     * Scope: Search by name or description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('status_name', 'LIKE', "%{$search}%")
              ->orWhere('data_type_name', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Get file extension from file path
     */
    public function getFileExtensionAttribute()
    {
        if (!$this->file) {
            return null;
        }
        return strtoupper(pathinfo($this->file, PATHINFO_EXTENSION));
    }

    /**
     * Get filename from file path
     */
    public function getFileNameAttribute()
    {
        if (!$this->file) {
            return null;
        }
        return basename($this->file);
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, H:i') : '-';
    }

    /**
     * Get truncated description
     */
    public function getTruncatedDescriptionAttribute()
    {
        return $this->description ? \Str::limit($this->description, 80) : null;
    }

    /**
     * Get truncated note
     */
    public function getTruncatedNoteAttribute()
    {
        return $this->note ? \Str::limit($this->note, 100) : null;
    }
}