<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\DetectedBuilding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetectedBuildingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = DetectedBuilding::query();
        if ($request->filled('status')) $q->where('verification_status', $request->status);
        if ($request->filled('source')) $q->where('detection_source', $request->source);
        if ($request->filled('district')) $q->where('building_district_name', $request->district);
        if ($request->boolean('unmatched_only')) $q->whereNull('matched_pbg_task_id');
        if ($request->filled('min_area')) $q->where('estimated_area_m2', '>=', $request->min_area);
        if ($request->filled('min_confidence')) $q->where('confidence_score', '>=', $request->min_confidence);
        if ($request->filled(['sw_lat','sw_lng','ne_lat','ne_lng'])) {
            $q->whereBetween('latitude', [$request->sw_lat, $request->ne_lat])
              ->whereBetween('longitude', [$request->sw_lng, $request->ne_lng]);
        }
        return response()->json($q->orderByDesc('created_at')->paginate(min((int)$request->get('per_page',50),500)));
    }
    public function show(int $id): JsonResponse { return response()->json(DetectedBuilding::with('matchedPbgTask')->findOrFail($id)); }
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['verification_status'=>'required|in:unverified,confirmed_illegal,confirmed_legal,false_positive,under_review','notes'=>'nullable|string|max:1000']);
        $b = DetectedBuilding::findOrFail($id);
        $b->update(['verification_status'=>$request->verification_status,'notes'=>$request->notes??$b->notes,'verified_by'=>$request->user()?->id,'verified_at'=>now()]);
        return response()->json($b);
    }
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate(['ids'=>'required|array|max:100','ids.*'=>'integer|exists:detected_buildings,id','verification_status'=>'required|in:unverified,confirmed_illegal,confirmed_legal,false_positive,under_review']);
        DetectedBuilding::whereIn('id',$request->ids)->update(['verification_status'=>$request->verification_status,'verified_by'=>$request->user()?->id,'verified_at'=>now()]);
        return response()->json(['updated'=>count($request->ids)]);
    }
    public function stats(Request $request): JsonResponse
    {
        $q = DetectedBuilding::query();
        if ($request->filled('source')) $q->where('detection_source',$request->source);
        $total = (clone $q)->count();
        $matched = (clone $q)->whereNotNull('matched_pbg_task_id')->count();
        $byStatus = (clone $q)->select('verification_status',DB::raw('COUNT(*) as count'))->groupBy('verification_status')->pluck('count','verification_status');
        $bySource = DetectedBuilding::select('detection_source',DB::raw('COUNT(*) as count'))->groupBy('detection_source')->pluck('count','detection_source');
        $byDistrict = (clone $q)->whereNull('matched_pbg_task_id')->whereNotNull('building_district_name')
            ->select('building_district_name',DB::raw('COUNT(*) as count'))->groupBy('building_district_name')->orderByDesc('count')->limit(20)->pluck('count','building_district_name');
        return response()->json(['total_detected'=>$total,'matched_with_permit'=>$matched,'unmatched_suspect'=>$total-$matched,
            'match_rate'=>$total>0?round($matched/$total*100,1):0,'by_verification_status'=>$byStatus,'by_source'=>$bySource,'unmatched_by_district'=>$byDistrict]);
    }
    public function geojson(Request $request): JsonResponse
    {
        $q = DetectedBuilding::query();
        if ($request->filled('status')) $q->where('verification_status',$request->status);
        if ($request->boolean('unmatched_only')) $q->whereNull('matched_pbg_task_id');
        if ($request->filled('district')) $q->where('building_district_name',$request->district);
        if ($request->filled('min_area')) $q->where('estimated_area_m2','>=',$request->min_area);
        if ($request->filled(['sw_lat','sw_lng','ne_lat','ne_lng'])) {
            $q->whereBetween('latitude',[$request->sw_lat,$request->ne_lat])->whereBetween('longitude',[$request->sw_lng,$request->ne_lng]);
        }
        $buildings = $q->select('id','latitude','longitude','estimated_area_m2','confidence_score','detection_source','verification_status','matched_pbg_task_id','building_district_name')->limit(5000)->get();
        $features = $buildings->map(fn($b)=>['type'=>'Feature','geometry'=>['type'=>'Point','coordinates'=>[(float)$b->longitude,(float)$b->latitude]],
            'properties'=>['id'=>$b->id,'area_m2'=>$b->estimated_area_m2,'confidence'=>$b->confidence_score,'source'=>$b->detection_source,'status'=>$b->verification_status,'has_permit'=>$b->matched_pbg_task_id!==null,'district'=>$b->building_district_name]]);
        return response()->json(['type'=>'FeatureCollection','features'=>$features->values()]);
    }
}
