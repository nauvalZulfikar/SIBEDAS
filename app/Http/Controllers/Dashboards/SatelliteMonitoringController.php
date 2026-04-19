<?php
namespace App\Http\Controllers\Dashboards;
use App\Http\Controllers\Controller;
class SatelliteMonitoringController extends Controller
{
    public function index() { return view('dashboards.satellite-monitoring'); }
}
