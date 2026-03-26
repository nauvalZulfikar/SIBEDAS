<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\SpatialPlanning;
use Illuminate\Http\Request;

class SpatialPlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        $permissions = $this->permissions[$menuId] ?? []; // Avoid undefined index error

        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;

        return view('data.spatialPlannings.index', compact('creator', 'updater', 'destroyer','menuId'));
    }

    /**
     * show the form for creating a new resource.
     */
    public function bulkCreate(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        return view('data.spatialPlannings.form-upload', compact('menuId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'Rencana Tata Ruang';
        $subtitle = "Create Data";

        // Mengambil data untuk dropdown
        $dropdownOptions = [];

        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/spatial-plannings');
        return view('data.spatialPlannings.form', compact('title', 'subtitle', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions','menuId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request,string $id)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'Rencana Tata Ruang';
        $subtitle = 'Update Data';

        $modelInstance = SpatialPlanning::find($id);
        // Pastikan model ditemukan
        if (!$modelInstance) {
            return redirect()->route('spatialPlanning.index') ->with('error', 'Rencana tata ruang tidak ditemukan');
        }

        $dropdownOptions = [];

        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/spatial-plannings');
        return view('data.spatialPlannings.form', compact('title', 'subtitle', 'modelInstance', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions','menuId'));
    }

    private function getFields()
    {
        return [
            "name"=> "Nama",
            "kbli"=> "KBLI",
            "activities"=> "Kegiatan",
            "area"=> "Luas (m2)",
            "land_area"=> "Luas Lahan (m2)",
            "location"=> "Lokasi",
            "number"=> "Nomor",
            "date"=> "Tanggal",
            "site_bcr"=> "BCR",
            "building_function"=> "Fungsi Bangunan",
            "business_type_info"=> "Jenis Usaha",
            "is_terbit"=> "Status Terbit",
            "calculated_retribution"=> "Retribusi",
        ];
    }

    private function getFieldTypes() 
    {
        return [
            "name"=> "text",
            "kbli"=> "text",
            "activities"=> "text",
            "area"=> "text",
            "land_area"=> "text",
            "location"=> "text",
            "number"=> "text",
            "date"=> "date",
            "site_bcr"=> "text",
            "building_function"=> "text",
            "business_type_info"=> "readonly",
            "is_terbit"=> "select",
            "calculated_retribution"=> "readonly",
        ];
    }
}
