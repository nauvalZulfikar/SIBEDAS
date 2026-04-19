<?php
namespace App\Console\Commands;
use App\Models\DetectedBuilding;
use App\Models\PbgTaskDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class EnrichBuildingDistricts extends Command
{
    protected $signature = 'buildings:enrich-districts {--max-distance=15000 : Max distance in meters}';
    protected $description = 'Assign kecamatan names to detected buildings using nearest PBG reference points';
    public function handle(): int
    {
        $this->info('=== Enriching District Info ===');
        $refs = PbgTaskDetail::whereNotNull('latitude')->whereNotNull('longitude')->where('latitude','!=',0)->where('longitude','!=',0)
            ->whereNotNull('building_district_name')->where('building_district_name','!=','N/A')
            ->select('latitude','longitude','building_district_name')->get();
        $this->info("PBG reference points: {$refs->count()}");
        $grid = []; $cs = 0.01;
        foreach ($refs as $r) {
            $k = floor((float)$r->latitude/$cs).':'.floor((float)$r->longitude/$cs);
            $grid[$k][] = ['lat'=>(float)$r->latitude,'lng'=>(float)$r->longitude,'district'=>$r->building_district_name];
        }
        $total = DetectedBuilding::whereNull('building_district_name')->count();
        $this->info("Buildings needing district: ".number_format($total));
        if ($total===0) { $this->info('Done!'); return self::SUCCESS; }
        $updated = 0; $maxDist = (int)$this->option('max-distance');
        DetectedBuilding::whereNull('building_district_name')->select('id','latitude','longitude')->chunkById(5000, function($buildings) use($grid,$cs,$maxDist,&$updated) {
            $byDistrict = [];
            foreach ($buildings as $b) {
                $lat=(float)$b->latitude; $lng=(float)$b->longitude;
                $cLat=floor($lat/$cs); $cLng=floor($lng/$cs);
                $best=null; $bestD=PHP_FLOAT_MAX;
                for($dL=-2;$dL<=2;$dL++) for($dN=-2;$dN<=2;$dN++) {
                    $k=($cLat+$dL).':'.($cLng+$dN);
                    if(!isset($grid[$k])) continue;
                    foreach($grid[$k] as $r) { $d=DetectedBuilding::haversineDistance($lat,$lng,$r['lat'],$r['lng']); if($d<$bestD){$bestD=$d;$best=$r;} }
                }
                if($best&&$bestD<=$maxDist) { $byDistrict[$best['district']][]=$b->id; }
            }
            foreach($byDistrict as $district=>$ids) {
                DB::table('detected_buildings')->whereIn('id',$ids)->update(['building_district_name'=>$district]);
                $updated+=count($ids);
            }
            if($updated%50000<5000) $this->info("  ... ".number_format($updated)." updated");
        });
        $this->info("Total updated: ".number_format($updated));
        return self::SUCCESS;
    }
}
