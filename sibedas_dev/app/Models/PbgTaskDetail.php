<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PbgTaskDetail extends Model
{
    protected $table = 'pbg_task_details';

    protected $fillable = [
        'pbg_task_uid',
        'uid',
        'nik',
        'type_card',
        'ownership',
        'owner_name',
        'ward_id',
        'ward_name',
        'district_id',
        'district_name',
        'regency_id',
        'regency_name',
        'province_id',
        'province_name',
        'address',
        'owner_email',
        'owner_phone',
        'user',
        'name',
        'email',
        'phone',
        'user_nik',
        'user_province_id',
        'user_province_name',
        'user_regency_id',
        'user_regency_name',
        'user_district_id',
        'user_district_name',
        'user_address',
        'status',
        'status_name',
        'slf_status',
        'slf_status_name',
        'sppst_status',
        'sppst_file',
        'sppst_status_name',
        'file_pbg',
        'file_pbg_date',
        'due_date',
        'start_date',
        'document_number',
        'registration_number',
        'function_type',
        'application_type',
        'application_type_name',
        'consultation_type',
        'condition',
        'prototype',
        'permanency',
        'building_type',
        'building_type_name',
        'building_purpose',
        'building_use',
        'occupancy',
        'name_building',
        'total_area',
        'area',
        'area_type',
        'height',
        'floor',
        'floor_area',
        'basement',
        'basement_height',
        'basement_area',
        'unit',
        'prev_retribution',
        'prev_pbg',
        'prev_total_area',
        'koefisien_dasar_bangunan',
        'koefisien_lantai_bangunan',
        'koefisien_lantai_hijau',
        'koefisien_tapak_basement',
        'ketinggian_bangunan',
        'jalan_arteri',
        'jalan_kolektor',
        'jalan_bangunan',
        'gsb',
        'kkr_number',
        'unit_data',
        'is_mbr',
        'code',
        'building_ward_id',
        'building_ward_name',
        'building_district_id',
        'building_district_name',
        'building_regency_id',
        'building_regency_name',
        'building_province_id',
        'building_province_name',
        'building_address',
        'latitude',
        'longitude',
        'building_photo',
        'pbg_parent',
        'api_created_at',
    ];

    protected $casts = [
        'unit_data' => 'array',
        'is_mbr' => 'boolean',
        'total_area' => 'decimal:2',
        'area' => 'decimal:2',
        'height' => 'decimal:2',
        'floor_area' => 'decimal:2',
        'basement_height' => 'decimal:2',
        'basement_area' => 'decimal:2',
        'prev_retribution' => 'decimal:2',
        'prev_total_area' => 'decimal:2',
        'koefisien_dasar_bangunan' => 'decimal:4',
        'koefisien_lantai_bangunan' => 'decimal:4',
        'koefisien_lantai_hijau' => 'decimal:4',
        'koefisien_tapak_basement' => 'decimal:4',
        'ketinggian_bangunan' => 'decimal:2',
        'gsb' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'file_pbg_date' => 'date',
        'due_date' => 'date',
        'start_date' => 'date',
        'api_created_at' => 'datetime',
    ];

    /**
     * Get the PBG task that owns this detail
     */
    public function pbgTask(): BelongsTo
    {
        return $this->belongsTo(PbgTask::class, 'pbg_task_uid', 'uuid');
    }



    /**
     * Helper method to clean and convert latitude/longitude values
     */
    private static function cleanCoordinate($value): ?float
    {
        if ($value === null || $value === '' || $value === '?' || $value === '-') {
            return null;
        }
        
        // Convert to string and trim whitespace
        $stringValue = trim((string) $value);
        
        // Check for common invalid values
        if (in_array($stringValue, ['', '?', '-', 'null', 'NULL', 'N/A', '0,'], true)) {
            return null;
        }
        
        // Remove degree symbol and other non-numeric characters except minus and decimal point
        $cleaned = preg_replace('/[^\d.-]/', '', $stringValue);
        
        // Check if cleaned value is empty or just a hyphen
        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return null;
        }
        
        // Validate if it's a valid number and within reasonable coordinate bounds
        if (is_numeric($cleaned)) {
            $coordinate = (float) $cleaned;
            
            // Basic validation for reasonable coordinate ranges
            // Latitude: -90 to 90, Longitude: -180 to 180
            if ($coordinate >= -180 && $coordinate <= 180) {
                return $coordinate;
            }
        }
        
        return null;
    }

    /**
     * Helper method to clean and convert integer values
     */
    private static function cleanIntegerValue($value): int
    {
        if ($value === null || $value === '' || $value === '?') {
            return 0;
        }
        
        // Convert to string and trim whitespace
        $stringValue = trim((string) $value);
        
        // Check for common invalid values
        if (in_array($stringValue, ['', '?', '-', 'null', 'NULL', 'N/A'], true)) {
            return 0;
        }
        
        // Remove any non-numeric characters except minus
        $cleaned = preg_replace('/[^\d-]/', '', $stringValue);
        
        // Check if cleaned value is empty or just invalid characters
        if ($cleaned === '' || $cleaned === '-') {
            return 0;
        }
        
        // Validate if it's a valid number
        if (is_numeric($cleaned)) {
            return (int) $cleaned;
        }
        
        return 0;
    }

    /**
     * Helper method to clean and convert numeric values
     */
    private static function cleanNumericValue($value, bool $nullable = false): ?float
    {
        if ($value === null || $value === '' || $value === '?') {
            return $nullable ? null : 0;
        }
        
        // Convert to string and trim whitespace
        $stringValue = trim((string) $value);
        
        // Check for common invalid values
        if (in_array($stringValue, ['', '?', '-', 'null', 'NULL', 'N/A'], true)) {
            return $nullable ? null : 0;
        }
        
        // Remove any non-numeric characters except minus and decimal point
        $cleaned = preg_replace('/[^\d.-]/', '', $stringValue);
        
        // Check if cleaned value is empty or just invalid characters
        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return $nullable ? null : 0;
        }
        
        // Validate if it's a valid number
        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }
        
        return $nullable ? null : 0;
    }

    /**
     * Helper method to handle date parsing with fallback
     */
    private static function parseDate($date): ?string
    {
        if (!$date || $date === '?' || $date === 'null') {
            return null;
        }
        
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper method to handle datetime parsing with fallback
     */
    private static function parseDateTime($datetime): ?string
    {
        if (!$datetime || $datetime === '?' || $datetime === 'null') {
            return null;
        }
        
        try {
            return Carbon::parse($datetime)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create or update PbgTaskDetail from API response
     */
    public static function createFromApiResponse(array $data, string $pbgTaskUuid): self
    {
        $detailData = [
            // Foreign key relationship - string, required
            'pbg_task_uid' => $pbgTaskUuid,
            
            // Basic information
            'uid' => $data['uid'] ?? "N/A", // string, unique, required
            'nik' => isset($data['nik']) && $data['nik'] !== '' && $data['nik'] !== '?' ? $data['nik'] : null, // string, nullable
            'type_card' => isset($data['type_card']) && $data['type_card'] !== '' && $data['type_card'] !== '?' ? $data['type_card'] : null, // string, nullable
            'ownership' => $data['ownership'] ?? null, // string, nullable
            'owner_name' => $data['owner_name'] ?? "N/A", // string, required
            
            // Owner location information - all required
            'ward_id' => self::cleanIntegerValue($data['ward_id'] ?? 0), // bigInteger, required
            'ward_name' => $data['ward_name'] ?? "N/A", // string, required
            'district_id' => self::cleanIntegerValue($data['district_id'] ?? 0), // integer, required
            'district_name' => $data['district_name'] ?? "N/A", // string, required
            'regency_id' => self::cleanIntegerValue($data['regency_id'] ?? 0), // integer, required
            'regency_name' => $data['regency_name'] ?? "N/A", // string, required
            'province_id' => self::cleanIntegerValue($data['province_id'] ?? 0), // integer, required
            'province_name' => $data['province_name'] ?? "N/A", // string, required
            'address' => $data['address'] ?? "N/A", // text, required
            
            // Owner contact information - required
            'owner_email' => $data['owner_email'] ?? "N/A", // string, required
            'owner_phone' => $data['owner_phone'] ?? "N/A", // string, required
            
            // User information - all required
            'user' => self::cleanIntegerValue($data['user'] ?? 0), // integer, required
            'name' => $data['name'] ?? "N/A", // string, required
            'email' => $data['email'] ?? "N/A", // string, required
            'phone' => $data['phone'] ?? "N/A", // string, required
            'user_nik' => $data['user_nik'] ?? "N/A", // string, required
            
            // User location information - all required
            'user_province_id' => self::cleanIntegerValue($data['user_province_id'] ?? 0), // integer, required
            'user_province_name' => $data['user_province_name'] ?? "N/A", // string, required
            'user_regency_id' => self::cleanIntegerValue($data['user_regency_id'] ?? 0), // integer, required
            'user_regency_name' => $data['user_regency_name'] ?? "N/A", // string, required
            'user_district_id' => self::cleanIntegerValue($data['user_district_id'] ?? 0), // integer, required
            'user_district_name' => $data['user_district_name'] ?? "N/A", // string, required
            'user_address' => $data['user_address'] ?? "N/A", // text, required
            
            // Status information
            'status' => self::cleanIntegerValue($data['status'] ?? 0), // integer, required
            'status_name' => $data['status_name'] ?? "N/A", // string, required
            'slf_status' => isset($data['slf_status']) && is_numeric($data['slf_status']) ? (int) $data['slf_status'] : null, // integer, nullable
            'slf_status_name' => $data['slf_status_name'] ?? null, // string, nullable
            'sppst_status' => self::cleanIntegerValue($data['sppst_status'] ?? 0), // integer, required
            'sppst_file' => $data['sppst_file'] ?? null, // string, nullable
            'sppst_status_name' => $data['sppst_status_name'] ?? "N/A", // string, required
            
            // Files and documents
            'file_pbg' => $data['file_pbg'] ?? null, // string, nullable
            'file_pbg_date' => self::parseDate($data['file_pbg_date'] ?? null), // date, nullable
            'due_date' => self::parseDate($data['due_date'] ?? null), // date, nullable
            'start_date' => self::parseDate($data['start_date'] ?? null) ?? now()->format('Y-m-d'), // date, required
            'document_number' => $data['document_number'] ?? null, // string, nullable
            'registration_number' => $data['registration_number'] ?? "N/A", // string, required
            
            // Application information - all nullable
            'function_type' => $data['function_type'] ?? null,
            'application_type' => $data['application_type'] ?? null,
            'application_type_name' => $data['application_type_name'] ?? null,
            'consultation_type' => $data['consultation_type'] ?? null,
            'condition' => $data['condition'] ?? null,
            'prototype' => $data['prototype'] ?? null,
            'permanency' => $data['permanency'] ?? null,
            
            // Building information - all nullable
            'building_type' => isset($data['building_type']) && is_numeric($data['building_type']) ? (int) $data['building_type'] : null, // integer, nullable
            'building_type_name' => $data['building_type_name'] ?? null,
            'building_purpose' => $data['building_purpose'] ?? null,
            'building_use' => $data['building_use'] ?? null,
            'occupancy' => $data['occupancy'] ?? null,
            'name_building' => $data['name_building'] ?? null,
            
            // Building dimensions and specifications
            'total_area' => self::cleanNumericValue($data['total_area'] ?? 0), // decimal(10,2), required
            'area' => self::cleanNumericValue($data['area'] ?? null, true), // decimal(10,2), nullable
            'area_type' => $data['area_type'] ?? null, // string, nullable
            'height' => self::cleanNumericValue($data['height'] ?? 0), // decimal(8,2), required
            'floor' => self::cleanIntegerValue($data['floor'] ?? 0), // integer, required
            'floor_area' => self::cleanNumericValue($data['floor_area'] ?? null, true), // decimal(10,2), nullable
            'basement' => isset($data['basement']) && $data['basement'] !== '' && $data['basement'] !== '?' ? $data['basement'] : null, // string, nullable
            'basement_height' => self::cleanNumericValue($data['basement_height'] ?? null, true), // decimal(8,2), nullable
            'basement_area' => self::cleanNumericValue($data['basement_area'] ?? 0), // decimal(10,2), required
            'unit' => isset($data['unit']) && is_numeric($data['unit']) ? (int) $data['unit'] : null, // integer, nullable
            
            // Previous information
            'prev_retribution' => self::cleanNumericValue($data['prev_retribution'] ?? null, true), // decimal(15,2), nullable
            'prev_pbg' => $data['prev_pbg'] ?? null, // string, nullable
            'prev_total_area' => self::cleanNumericValue($data['prev_total_area'] ?? null, true), // decimal(10,2), nullable
            
            // Coefficients - all nullable, decimal(8,4)
            'koefisien_dasar_bangunan' => self::cleanNumericValue($data['koefisien_dasar_bangunan'] ?? null, true),
            'koefisien_lantai_bangunan' => self::cleanNumericValue($data['koefisien_lantai_bangunan'] ?? null, true),
            'koefisien_lantai_hijau' => self::cleanNumericValue($data['koefisien_lantai_hijau'] ?? null, true),
            'koefisien_tapak_basement' => self::cleanNumericValue($data['koefisien_tapak_basement'] ?? null, true),
            'ketinggian_bangunan' => self::cleanNumericValue($data['ketinggian_bangunan'] ?? null, true), // decimal(8,2), nullable
            
            // Road information - all nullable
            'jalan_arteri' => $data['jalan_arteri'] ?? null,
            'jalan_kolektor' => $data['jalan_kolektor'] ?? null,
            'jalan_bangunan' => $data['jalan_bangunan'] ?? null,
            'gsb' => self::cleanNumericValue($data['gsb'] ?? null, true), // decimal(8,2), nullable
            'kkr_number' => $data['kkr_number'] ?? null, // string, nullable
            
            // Unit data as JSON - nullable
            'unit_data' => $data['unit_data'] ?? null,
            
            // Additional flags
            'is_mbr' => (bool) ($data['is_mbr'] ?? false), // boolean, default false
            'code' => $data['code'] ?? "N/A", // string, required
            
            // Building location information - all required
            'building_ward_id' => self::cleanIntegerValue($data['building_ward_id'] ?? 0), // bigInteger, required
            'building_ward_name' => $data['building_ward_name'] ?? "N/A", // string, required
            'building_district_id' => self::cleanIntegerValue($data['building_district_id'] ?? 0), // integer, required
            'building_district_name' => $data['building_district_name'] ?? "N/A", // string, required
            'building_regency_id' => self::cleanIntegerValue($data['building_regency_id'] ?? 0), // integer, required
            'building_regency_name' => $data['building_regency_name'] ?? "N/A", // string, required
            'building_province_id' => self::cleanIntegerValue($data['building_province_id'] ?? 0), // integer, required
            'building_province_name' => $data['building_province_name'] ?? "N/A", // string, required
            'building_address' => $data['building_address'] ?? "N/A", // text, required
            
            // Coordinates - decimal(15,8), nullable
            'latitude' => self::cleanCoordinate($data['latitude'] ?? null),
            'longitude' => self::cleanCoordinate($data['longitude'] ?? null),
            
            // Additional files - nullable
            'building_photo' => $data['building_photo'] ?? null,
            'pbg_parent' => $data['pbg_parent'] ?? null,
            
            // Original created_at from API - nullable
            'api_created_at' => self::parseDateTime($data['created_at'] ?? null),
        ];

        return static::updateOrCreate(
            ['uid' => $data['uid']],
            $detailData
        );
    }


}
