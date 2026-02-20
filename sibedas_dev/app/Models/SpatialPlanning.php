<?php

namespace App\Models;

use App\Traits\HasRetributionCalculation;
use Illuminate\Database\Eloquent\Model;


/**
 * Class SpatialPlanning
 *
 * @property $id
 * @property $created_at
 * @property $updated_at
 * @property $name
 * @property $kbli
 * @property $activities
 * @property $area
 * @property $location
 * @property $number
 * @property $date
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class SpatialPlanning extends Model
{
    use HasRetributionCalculation;
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'kbli', 'activities', 'area', 'location', 'number', 'date', 'no_tapak', 'no_skkl', 'no_ukl', 'building_function', 'sub_building_function', 'number_of_floors', 'land_area', 'site_bcr', 'is_terbit'];

    protected $casts = [
        'area' => 'decimal:6',
        'land_area' => 'decimal:6',
        'site_bcr' => 'decimal:6',
        'number_of_floors' => 'integer',
        'date' => 'date',
        'is_terbit' => 'boolean'
    ];

    protected $appends = [
        'calculated_retribution',
        'formatted_retribution',
        'is_business_type',
        'calculation_details',
        'old_calculation_amount',
        'calculation_source'
    ];



    /**
     * Get building function text for detection
     */
    public function getBuildingFunctionText(): string
    {
        return $this->building_function ?? $this->activities ?? '';
    }

    /**
     * Get area for calculation (prioritize area, fallback to land_area)
     */
    public function getCalculationArea(): float
    {
        return (float) ($this->area ?? $this->land_area ?? 0);
    }

    /**
     * Get calculated retribution amount
     * Priority: Manual calculation (new formula) > Active calculation (old system)
     */
    public function getCalculatedRetributionAttribute(): float
    {
        try {
            // PRIORITY 1: Use new manual formula (LUAS LAHAN × BCR × HARGA SATUAN)
            $manualCalculation = $this->calculateRetributionManually();
            
            // If manual calculation is valid (> 0), use it
            if ($manualCalculation > 0) {
                return $manualCalculation;
            }
            
            // PRIORITY 2: Fallback to active retribution calculation if exists
            $activeCalculation = $this->activeRetributionCalculation;
            
            if ($activeCalculation && $activeCalculation->retributionCalculation) {
                return (float) $activeCalculation->retributionCalculation->retribution_amount;
            }
            
            // PRIORITY 3: Return 0 if nothing works
            return 0.0;
            
        } catch (\Exception $e) {
            \Log::warning('Failed to calculate retribution for SpatialPlanning ID: ' . $this->id, [
                'error' => $e->getMessage(),
                'spatial_planning' => $this->toArray()
            ]);
            return 0.0;
        }
    }

    /**
     * Manual calculation based on area and building function
     * Formula: LUAS LAHAN × BCR × HARGA SATUAN
     * NON USAHA: 16,000 per m2
     * USAHA: 44,300 per m2
     */
    private function calculateRetributionManually(): float
    {
        // Get land area (luas lahan)
        $landArea = (float) ($this->land_area ?? 0);
        
        // Get BCR (Building Coverage Ratio) - convert from percentage to decimal
        $bcrPercentage = (float) ($this->site_bcr ?? 0);
        $bcr = $bcrPercentage / 100; // Convert percentage to decimal (24.49% -> 0.2449)
        
        if ($landArea <= 0 || $bcr <= 0) {
            return 0.0;
        }
        
        // Determine if this is business (USAHA) or non-business (NON USAHA)
        $isBusiness = $this->isBusinessType();
        
        // Set unit price based on business type
        $unitPrice = $isBusiness ? 44300 : 16000;
        
        // Calculate: LUAS LAHAN × BCR (as decimal) × HARGA SATUAN
        $calculatedAmount = $landArea * $bcr * $unitPrice;
        
        return $calculatedAmount;
    }

    /**
     * Determine if this spatial planning is for business purposes
     */
    private function isBusinessType(): bool
    {
        $buildingFunction = strtolower($this->building_function ?? $this->activities ?? '');
        
        // Business keywords
        $businessKeywords = [
            'usaha', 'dagang', 'perdagangan', 'komersial', 'commercial', 'bisnis', 'business',
            'toko', 'warung', 'pasar', 'kios', 'mall', 'plaza', 'supermarket', 'department',
            'hotel', 'resort', 'restoran', 'restaurant', 'cafe', 'kantor', 'perkantoran', 'office',
            'industri', 'pabrik', 'gudang', 'warehouse', 'manufacturing', 'produksi',
            'bengkel', 'workshop', 'showroom', 'dealer', 'apotek', 'pharmacy', 'klinik swasta',
            'rumah sakit swasta', 'bank', 'atm', 'money changer', 'asuransi', 'leasing',
            'rental', 'sewa', 'jasa', 'service', 'salon', 'spa', 'fitness', 'gym',
            'tempat usaha', 'fungsi usaha', 'kegiatan usaha', 'bangunan usaha'
        ];
        
        // Check if any business keyword is found
        foreach ($businessKeywords as $keyword) {
            if (str_contains($buildingFunction, $keyword)) {
                return true;
            }
        }
        
        // Non-business (default)
        return false;
    }

    /**
     * Get formatted retribution amount for display
     */
    public function getFormattedRetributionAttribute(): string
    {
        $amount = $this->calculated_retribution;
        return number_format($amount, 0, ',', '.');
    }

    /**
     * Check if this is business type
     */
    public function getIsBusinessTypeAttribute(): bool
    {
        return $this->isBusinessType();
    }

    /**
     * Get calculation details for transparency
     */
    public function getCalculationDetailsAttribute(): array
    {
        $landArea = (float) ($this->land_area ?? 0);
        $bcrPercentage = (float) ($this->site_bcr ?? 0);
        $bcr = $bcrPercentage / 100; // Convert to decimal
        $isBusiness = $this->isBusinessType();
        $unitPrice = $isBusiness ? 44300 : 16000;
        $calculatedAmount = $landArea * $bcr * $unitPrice;

        return [
            'formula' => 'LUAS LAHAN × BCR (decimal) × HARGA SATUAN',
            'land_area' => $landArea,
            'bcr_percentage' => $bcrPercentage,
            'bcr_decimal' => $bcr,
            'business_type' => $isBusiness ? 'USAHA' : 'NON USAHA',
            'unit_price' => $unitPrice,
            'calculation' => "{$landArea} × {$bcr} × {$unitPrice}",
            'result' => $calculatedAmount,
            'building_function' => $this->building_function ?? $this->activities ?? 'N/A'
        ];
    }

    /**
     * Get old calculation amount from database
     */
    public function getOldCalculationAmountAttribute(): float
    {
        $activeCalculation = $this->activeRetributionCalculation;
        
        if ($activeCalculation && $activeCalculation->retributionCalculation) {
            return (float) $activeCalculation->retributionCalculation->retribution_amount;
        }
        
        return 0.0;
    }

    /**
     * Get calculation source info
     */
    public function getCalculationSourceAttribute(): string
    {
        $manualCalculation = $this->calculateRetributionManually();
        $hasActiveCalculation = $this->hasActiveRetributionCalculation();
        
        if ($manualCalculation > 0) {
            return $hasActiveCalculation ? 'NEW_FORMULA' : 'NEW_FORMULA_ONLY';
        } elseif ($hasActiveCalculation) {
            return 'OLD_DATABASE';
        }
        
        return 'NONE';
    }
}
