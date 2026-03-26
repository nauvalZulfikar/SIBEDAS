<?php

namespace App\Services;

use App\Models\BuildingType;
use App\Models\HeightIndex;
use App\Models\RetributionConfig;
use App\Models\RetributionCalculation;

class RetributionCalculatorService
{
    /**
     * Calculate retribution for given parameters
     */
    public function calculate(
        int $buildingTypeId,
        int $floorNumber,
        float $buildingArea,
        bool $saveResult = true,
        bool $excelCompatibleMode = false
    ): array {
        // Get building type with indices
        $buildingType = BuildingType::with('indices')->findOrFail($buildingTypeId);
        
        // Check if building type is free
        if ($buildingType->isFree()) {
            return $this->createFreeResult($buildingType, $floorNumber, $buildingArea, $saveResult);
        }

        // Get height index
        $heightIndex = HeightIndex::getHeightIndexByFloor($floorNumber);
        
        // Get configuration values
        $baseValue = RetributionConfig::getValue('BASE_VALUE', 70350);
        $infrastructureMultiplier = RetributionConfig::getValue('INFRASTRUCTURE_MULTIPLIER', 0.5);
        $heightMultiplier = RetributionConfig::getValue('HEIGHT_MULTIPLIER', 0.5);

        // Get indices
        $indices = $buildingType->indices;
        if (!$indices) {
            throw new \Exception("Indices not found for building type: {$buildingType->name}");
        }

        // Calculate using Excel formula
        $result = $this->executeCalculation(
            $buildingType,
            $indices,
            $heightIndex,
            $baseValue,
            $infrastructureMultiplier,
            $heightMultiplier,
            $floorNumber,
            $buildingArea,
            $excelCompatibleMode
        );

        // Save result if requested
        if ($saveResult) {
            $calculation = RetributionCalculation::createCalculation(
                $buildingTypeId,
                $floorNumber,
                $buildingArea,
                $result['total_retribution'],
                $result['calculation_detail']
            );
            $result['calculation_id'] = $calculation->calculation_id;
        }

        return $result;
    }

    /**
     * Execute the main calculation logic
     */
    protected function executeCalculation(
        BuildingType $buildingType,
        $indices,
        float $heightIndex,
        float $baseValue,
        float $infrastructureMultiplier,
        float $heightMultiplier,
        int $floorNumber,
        float $buildingArea,
        bool $excelCompatibleMode = false
    ): array {
        // Step 1: Calculate H5 coefficient (Excel formula: RUNDOWN(($E5*($F5+$G5+(0.5*H$3))),4))
        // H5 = coefficient * (ip_permanent + ip_complexity + (height_multiplier * height_index))
        $h5Raw = $indices->coefficient * (
            $indices->ip_permanent + 
            $indices->ip_complexity + 
            ($heightMultiplier * $heightIndex)
        );
        
        // Apply RUNDOWN (floor to 4 decimal places)
        $h5 = floor($h5Raw * 10000) / 10000;

        // Step 2: Main calculation (Excel: 1*D5*(N5*base_value*H5*1))
        // Main = building_area * locality_index * base_value * h5
        $mainCalculation = $buildingArea * $indices->locality_index * $baseValue * $h5;

        // Step 3: Infrastructure calculation (Excel: O3*(1*D5*(N5*base_value*H5*1)))
        // Additional = infrastructure_multiplier * main_calculation
        $infrastructureCalculation = $infrastructureMultiplier * $mainCalculation;

        // Step 4: Total retribution (Main + Infrastructure)
        if ($excelCompatibleMode) {
            // Try to match Excel exactly - round intermediate calculations
            $mainCalculation = round($mainCalculation, 0);
            $infrastructureCalculation = round($infrastructureCalculation, 0);
            $totalRetribution = $mainCalculation + $infrastructureCalculation;
        } else {
            // Apply standard rounding to match Excel results more closely
            $totalRetribution = round($mainCalculation + $infrastructureCalculation, 0);
        }

        return [
            'building_type' => [
                'id' => $buildingType->id,
                'code' => $buildingType->code,
                'name' => $buildingType->name,
                'is_free' => $buildingType->is_free
            ],
            'input_parameters' => [
                'building_area' => $buildingArea,
                'floor_number' => $floorNumber,
                'height_index' => $heightIndex,
                'base_value' => $baseValue,
                'infrastructure_multiplier' => $infrastructureMultiplier,
                'height_multiplier' => $heightMultiplier
            ],
            'indices' => [
                'coefficient' => $indices->coefficient,
                'ip_permanent' => $indices->ip_permanent,
                'ip_complexity' => $indices->ip_complexity,
                'locality_index' => $indices->locality_index,
                'infrastructure_factor' => $indices->infrastructure_factor
            ],
            'calculation_steps' => [
                'h5_coefficient' => [
                    'formula' => 'RUNDOWN((coefficient * (ip_permanent + ip_complexity + (height_multiplier * height_index))), 4)',
                    'calculation' => "RUNDOWN(({$indices->coefficient} * ({$indices->ip_permanent} + {$indices->ip_complexity} + ({$heightMultiplier} * {$heightIndex}))), 4)",
                    'raw_result' => $h5Raw,
                    'result' => $h5
                ],
                'main_calculation' => [
                    'formula' => 'building_area * locality_index * base_value * h5',
                    'calculation' => "{$buildingArea} * {$indices->locality_index} * {$baseValue} * {$h5}",
                    'result' => $mainCalculation
                ],
                'infrastructure_calculation' => [
                    'formula' => 'infrastructure_multiplier * main_calculation',
                    'calculation' => "{$infrastructureMultiplier} * {$mainCalculation}",
                    'result' => $infrastructureCalculation
                ],
                'total_calculation' => [
                    'formula' => 'main_calculation + infrastructure_calculation',
                    'calculation' => "{$mainCalculation} + {$infrastructureCalculation}",
                    'result' => $totalRetribution
                ]
            ],
            'total_retribution' => $totalRetribution,
            'formatted_amount' => 'Rp ' . number_format($totalRetribution, 2, ',', '.'),
            'calculation_detail' => [
                'h5_raw' => $h5Raw,
                'h5' => $h5,
                'main' => $mainCalculation,
                'infrastructure' => $infrastructureCalculation,
                'total' => $totalRetribution
            ]
        ];
    }

    /**
     * Create result for free building types
     */
    protected function createFreeResult(
        BuildingType $buildingType,
        int $floorNumber,
        float $buildingArea,
        bool $saveResult
    ): array {
        $result = [
            'building_type' => [
                'id' => $buildingType->id,
                'code' => $buildingType->code,
                'name' => $buildingType->name,
                'is_free' => true
            ],
            'input_parameters' => [
                'building_area' => $buildingArea,
                'floor_number' => $floorNumber
            ],
            'total_retribution' => 0.0,
            'formatted_amount' => 'Rp 0 (Gratis)',
            'calculation_detail' => [
                'reason' => 'Building type is free of charge',
                'total' => 0.0
            ]
        ];

        if ($saveResult) {
            $calculation = RetributionCalculation::createCalculation(
                $buildingType->id,
                $floorNumber,
                $buildingArea,
                0.0,
                $result['calculation_detail']
            );
            $result['calculation_id'] = $calculation->calculation_id;
        }

        return $result;
    }

    /**
     * Get calculation by ID
     */
    public function getCalculationById(string $calculationId): ?RetributionCalculation
    {
        return RetributionCalculation::with('buildingType')
            ->where('calculation_id', $calculationId)
            ->first();
    }

    /**
     * Get all available building types for calculation
     */
    public function getAvailableBuildingTypes(): array
    {
        return BuildingType::with('indices')
            ->active()
            ->children() // Only child types can be used for calculation
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'is_free' => $type->is_free,
                    'has_indices' => $type->indices !== null,
                    'coefficient' => $type->indices ? $type->indices->coefficient : null
                ];
            })
            ->toArray();
    }

    /**
     * Get all available floor numbers
     */
    public function getAvailableFloors(): array
    {
        return HeightIndex::getAvailableFloors();
    }
} 