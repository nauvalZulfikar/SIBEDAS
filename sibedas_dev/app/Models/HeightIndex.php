<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeightIndex extends Model
{
    protected $fillable = [
        'floor_number',
        'height_index'
    ];

    protected $casts = [
        'floor_number' => 'integer',
        'height_index' => 'decimal:6'
    ];

    /**
     * Get height index by floor number
     */
    public static function getByFloor(int $floorNumber): ?HeightIndex
    {
        return self::where('floor_number', $floorNumber)->first();
    }

    /**
     * Get height index value by floor number
     */
    public static function getHeightIndexByFloor(int $floorNumber): float
    {
        $index = self::getByFloor($floorNumber);
        return $index ? (float) $index->height_index : 1.0;
    }

    /**
     * Get all height indices as array
     */
    public static function getAllMapping(): array
    {
        return self::orderBy('floor_number')
            ->pluck('height_index', 'floor_number')
            ->toArray();
    }

    /**
     * Get available floor numbers
     */
    public static function getAvailableFloors(): array
    {
        return self::orderBy('floor_number')
            ->pluck('floor_number')
            ->toArray();
    }
} 