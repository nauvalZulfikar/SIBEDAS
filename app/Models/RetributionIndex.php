<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetributionIndex extends Model
{
    protected $fillable = [
        'building_type_id',
        'coefficient',
        'ip_permanent',
        'ip_complexity',
        'locality_index',
        'infrastructure_factor',
        'is_active'
    ];

    protected $casts = [
        'coefficient' => 'decimal:4',
        'ip_permanent' => 'decimal:4',
        'ip_complexity' => 'decimal:4',
        'locality_index' => 'decimal:4',
        'infrastructure_factor' => 'decimal:4',
        'is_active' => 'boolean'
    ];

    /**
     * Building type relationship
     */
    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'building_type_id');
    }

    /**
     * Scope: Active only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all indices as array
     */
    public function getIndicesArray(): array
    {
        return [
            'ip_permanent' => $this->ip_permanent,
            'ip_complexity' => $this->ip_complexity,
            'locality_index' => $this->locality_index,
            'infrastructure_factor' => $this->infrastructure_factor
        ];
    }
} 