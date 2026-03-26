<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BuildingType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'level',
        'is_free',
        'is_active'
    ];

    protected $casts = [
        'level' => 'integer',
        'is_free' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Parent relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'parent_id');
    }

    /**
     * Children relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(BuildingType::class, 'parent_id')
                    ->where('is_active', true);
    }

    /**
     * Retribution indices relationship
     */
    public function indices(): HasOne
    {
        return $this->hasOne(RetributionIndex::class, 'building_type_id');
    }

    /**
     * Calculations relationship
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(RetributionCalculation::class, 'building_type_id');
    }

    /**
     * Scope: Active only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Parents only
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Children only
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope: Non-free types
     */
    public function scopeChargeable($query)
    {
        return $query->where('is_free', false);
    }

    /**
     * Check if building type is free
     */
    public function isFree(): bool
    {
        return $this->is_free;
    }

    /**
     * Check if this is a parent type
     */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this is a child type
     */
    public function isChild(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get complete data for calculation
     */
    public function getCalculationData(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'coefficient' => $this->coefficient,
            'is_free' => $this->is_free,
            'indices' => $this->indices?->toArray(),
            'parent' => $this->parent?->only(['id', 'code', 'name'])
        ];
    }
} 