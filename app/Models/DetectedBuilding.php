<?php
namespace App\Models;
use App\Observers\DetectedBuildingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([DetectedBuildingObserver::class])]
class DetectedBuilding extends Model
{
    protected $fillable = ['latitude','longitude','estimated_area_m2','confidence_score','detection_source','detection_date','geometry_geojson','matched_pbg_task_id','pbb_record_id','match_distance_m','verification_status','building_district_name','building_ward_name','verified_by','verified_at','notes'];
    protected $casts = ['latitude'=>'decimal:8','longitude'=>'decimal:8','estimated_area_m2'=>'decimal:2','confidence_score'=>'decimal:3','match_distance_m'=>'decimal:2','geometry_geojson'=>'array','detection_date'=>'date','verified_at'=>'datetime'];
    public function matchedPbgTask() { return $this->belongsTo(PbgTask::class, 'matched_pbg_task_id'); }
    public function pbbRecord() { return $this->belongsTo(PbbRecord::class, 'pbb_record_id'); }
    public function verifiedByUser() { return $this->belongsTo(User::class, 'verified_by'); }
    public function scopeUnmatched($q) { return $q->whereNull('matched_pbg_task_id'); }
    public function scopeUnmatchedPbb($q) { return $q->whereNull('pbb_record_id'); }
    public function scopeUnverified($q) { return $q->where('verification_status', 'unverified'); }
    public function scopeByDistrict($q, string $d) { return $q->where('building_district_name', $d); }
    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000; $dLat = deg2rad($lat2-$lat1); $dLng = deg2rad($lng2-$lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }
}
