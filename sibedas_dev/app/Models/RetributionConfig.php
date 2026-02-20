<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetributionConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get config value by key
     */
    public static function getValue(string $key, float $default = 0.0): float
    {
        $config = self::where('key', $key)->where('is_active', true)->first();
        return $config ? (float) $config->value : $default;
    }

    /**
     * Get all active configs as array
     */
    public static function getAllActive(): array
    {
        return self::where('is_active', true)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Update config value
     */
    public static function updateValue(string $key, float $value): bool
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'is_active' => true]
        );
    }
} 