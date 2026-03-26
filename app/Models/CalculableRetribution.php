<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalculableRetribution extends Model
{
    protected $fillable = [
        'retribution_calculation_id',
        'calculable_id',
        'calculable_type',
        'is_active',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'timestamp',
    ];

    /**
     * Get the owning calculable model (polymorphic)
     */
    public function calculable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the retribution calculation
     */
    public function retributionCalculation(): BelongsTo
    {
        return $this->belongsTo(RetributionCalculation::class);
    }

    /**
     * Scope: Only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only inactive assignments
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: For specific calculable type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('calculable_type', $type);
    }
}
