<?php

namespace App\Traits;

use App\Models\RetributionCalculation;
use App\Models\CalculableRetribution;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasRetributionCalculation
{
    /**
     * Get all retribution calculations for this model (polymorphic many-to-many)
     */
    public function retributionCalculations(): MorphMany
    {
        return $this->morphMany(CalculableRetribution::class, 'calculable');
    }

    /**
     * Get active retribution calculation
     */
    public function activeRetributionCalculation(): MorphOne
    {
        return $this->morphOne(CalculableRetribution::class, 'calculable')
                    ->where('is_active', true)
                    ->latest('assigned_at');
    }

    /**
     * Assign calculation to this model
     */
    public function assignRetributionCalculation(RetributionCalculation $calculation, string $notes = null): CalculableRetribution
    {
        // Deactivate previous active calculation
        $this->retributionCalculations()
             ->where('is_active', true)
             ->update(['is_active' => false]);

        // Create new assignment
        return $this->retributionCalculations()->create([
            'retribution_calculation_id' => $calculation->id,
            'is_active' => true,
            'assigned_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get current retribution amount
     */
    public function getCurrentRetributionAmount(): float
    {
        $activeCalculation = $this->activeRetributionCalculation;
        
        return $activeCalculation 
            ? $activeCalculation->retributionCalculation->retribution_amount 
            : 0;
    }

    /**
     * Check if has active calculation
     */
    public function hasActiveRetributionCalculation(): bool
    {
        return $this->activeRetributionCalculation()->exists();
    }

    /**
     * Get calculation history
     */
    public function getRetributionCalculationHistory()
    {
        return $this->retributionCalculations()
                    ->with('retributionCalculation')
                    ->orderBy('assigned_at', 'desc')
                    ->get();
    }
} 