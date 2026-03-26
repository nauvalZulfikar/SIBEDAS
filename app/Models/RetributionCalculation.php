<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class RetributionCalculation extends Model
{
    protected $fillable = [
        'calculation_id',
        'building_type_id',
        'floor_number',
        'building_area',
        'retribution_amount',
        'calculation_detail',
        'calculated_at',
    ];

    protected $casts = [
        'building_area' => 'decimal:2',
        'retribution_amount' => 'decimal:2',
        'calculation_detail' => 'array',
        'calculated_at' => 'timestamp',
        'floor_number' => 'integer',
    ];

    /**
     * Get the building type
     */
    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }

    /**
     * Get all calculable assignments
     */
    public function calculableRetributions(): HasMany
    {
        return $this->hasMany(CalculableRetribution::class);
    }

    /**
     * Get active assignments only
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(CalculableRetribution::class)->where('is_active', true);
    }

    /**
     * Generate unique calculation ID
     */
    public static function generateCalculationId(): string
    {
        $maxAttempts = 10;
        $attempt = 0;
        
        do {
            // Use microseconds for better uniqueness but keep within 20 char limit
            // Format: CALC-YYYYMMDD-XXXXX (20 chars exactly)
            $microseconds = (int) (microtime(true) * 1000) % 100000; // 5 digits max
            $id = 'CALC-' . date('Ymd') . '-' . str_pad($microseconds, 5, '0', STR_PAD_LEFT);
            
            // Check if ID already exists
            if (!self::where('calculation_id', $id)->exists()) {
                return $id;
            }
            
            $attempt++;
            // Add small delay to ensure different microsecond values
            usleep(1000); // 1ms delay
            
        } while ($attempt < $maxAttempts);
        
        // Fallback to random 5-digit number if all attempts fail
        for ($i = 0; $i < 100; $i++) {
            $random = mt_rand(10000, 99999);
            $id = 'CALC-' . date('Ymd') . '-' . $random;
            
            if (!self::where('calculation_id', $id)->exists()) {
                return $id;
            }
        }
        
        // Final fallback - use current timestamp seconds
        return 'CALC-' . date('Ymd') . '-' . str_pad(time() % 100000, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate calculation_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->calculation_id)) {
                $model->calculation_id = self::generateCalculationId();
            }
            if (empty($model->calculated_at)) {
                $model->calculated_at = now();
            }
        });
    }

    /**
     * Check if calculation is being used
     */
    public function isInUse(): bool
    {
        return $this->activeAssignments()->exists();
    }

    /**
     * Get calculation summary
     */
    public function getSummary(): array
    {
        return [
            'calculation_id' => $this->calculation_id,
            'building_type' => $this->buildingType->name ?? 'Unknown',
            'floor_number' => $this->floor_number,
            'building_area' => $this->building_area,
            'retribution_amount' => $this->retribution_amount,
            'calculated_at' => $this->calculated_at->format('Y-m-d H:i:s'),
            'in_use' => $this->isInUse(),
        ];
    }

    /**
     * Create new calculation
     */
    public static function createCalculation(
        int $buildingTypeId,
        int $floorNumber,
        float $buildingArea,
        float $retributionAmount,
        array $calculationDetail
    ): self {
        return self::create([
            'calculation_id' => self::generateCalculationId(),
            'building_type_id' => $buildingTypeId,
            'floor_number' => $floorNumber,
            'building_area' => $buildingArea,
            'retribution_amount' => $retributionAmount,
            'calculation_detail' => $calculationDetail,
            'calculated_at' => Carbon::now()
        ]);
    }

    /**
     * Get formatted retribution amount
     */
    public function getFormattedAmount(): string
    {
        return 'Rp ' . number_format($this->retribution_amount, 2, ',', '.');
    }

    /**
     * Get calculation breakdown
     */
    public function getCalculationBreakdown(): array
    {
        return $this->calculation_detail ?? [];
    }
} 