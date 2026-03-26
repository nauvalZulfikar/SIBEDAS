<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UmkmController extends Controller
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
        return view('data.umkm.index', compact('creator', 'updater', 'destroyer', 'menuId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function bulkCreate(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        return view('data.umkm.form-upload', compact('menuId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'UMKM';
        $subtitle = 'Create Data';

        // Mengambil data untuk dropdown
        $dropdownOptions = [
            'village_name' => DB::table('villages')->orderBy('village_name')->pluck('village_name', 'village_code'),
            'district_name' => DB::table('districts')->orderBy('district_name')->pluck('district_name', 'district_code'),
            'business_scale_id' => DB::table('business_scale')->orderBy('business_scale')->pluck('business_scale', 'id'),
            'permit_status_id' => DB::table('permit_status')->orderBy('permit_status')->pluck('permit_status', 'id'),
            'business_form_id' => DB::table('business_form')->orderBy('business_form')->pluck('business_form', 'id')
        ];

        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/umkm');

        return view('data.umkm.form', compact('title', 'subtitle', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions','menuId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request,$id)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'UMKM';
        $subtitle = 'Update Data';
        $modelInstance = Umkm::find($id);
        // Pastikan model ditemukan
        if (!$modelInstance) {
            return redirect()->route('web-umkm.index')->with('error', 'Umkm not found');
        }

        // Mengambil dan memetakan village_name dan district_name
        $village = DB::table('villages')->where('village_code', $modelInstance->village_code)->first();
        $modelInstance->village_name = $village ? $village->village_name : null;

        $district = DB::table('districts')->where('district_code', $modelInstance->district_code)->first();
        $modelInstance->district_name = $district ? $district->district_name : null;

        $business_scale = DB::table('business_scale')->where('id', $modelInstance->business_scale_id)->first();
        $modelInstance->business_scale_id = $business_scale ? $business_scale->id : null;
        
        $permit_status = DB::table('permit_status')->where('id', $modelInstance->permit_status_id)->first();
        $modelInstance->permit_status_id = $permit_status ? $permit_status->id : null;

        $business_form = DB::table('business_form')->where('id', $modelInstance->business_form_id)->first();
        $modelInstance->business_form_id = $business_form ? $business_form->id : null;

        // dd($modelInstance['business_form_id']);
        // Mengambil data untuk dropdown
        $dropdownOptions = [
            'village_name' => DB::table('villages')->orderBy('village_name')->pluck('village_name', 'village_code'),
            'district_name' => DB::table('districts')->orderBy('district_name')->pluck('district_name', 'district_code'),
            'business_scale_id' => DB::table('business_scale')->orderBy('business_scale')->pluck('business_scale', 'id'),
            'permit_status_id' => DB::table('permit_status')->orderBy('permit_status')->pluck('permit_status', 'id'),
            'business_form_id' => DB::table('business_form')->orderBy('business_form')->pluck('business_form', 'id')
        ];

        info("AdvertisementController@edit diakses dengan Model Instance: $modelInstance");
        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/umkm');

        // dd($modelInstance->business_form_id, $dropdownOptions['business_form']);
        return view('data.umkm.form', compact('title', 'subtitle', 'modelInstance', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions','menuId'));
    }

    private function getFields()
    {
        return [
            "business_name" => "Nama Usaha",
            "business_address" => "Alamat Usaha",
            "business_desc" => "Deskripsi Usaha",
            "business_contact" => "Kontak Usaha",
            "business_id_number" => "NIB",
            "business_scale_id" => "Skala Usaha",
            "owner_id" => "NIK",
            "owner_name" => "Nama Pemilik",
            "owner_address" => "Alamat Pemilik",
            "owner_contact" => "Kontak Pemilik",
            "business_type" => "Jenis Usaha",
            "district_name" => "Kecamatan",
            "village_name" => "Desa",
            "number_of_employee" => "Jumlah Karyawan",
            "land_area" => "Luas Tanah",
            "permit_status_id" => "Ijin Status",
            "business_form_id" => "Bisnis Form",
            "revenue" => "Omset"
        ];
    }

    private function getFieldTypes()
    {
        return [
            "business_name" => "text",
            "business_address" => "text",
            "business_desc" => "textarea",
            "business_contact" => "text",
            "business_id_number" => "text",
            "business_scale_id" => "select",
            "owner_id" => "text",
            "owner_name" => "text",
            "owner_address" => "text",
            "owner_contact" => "text",
            "business_type" => "text",
            "district_name" => "combobox",
            "village_name" => "combobox",
            "number_of_employee" => "text",
            "land_area" => "text",
            "permit_status_id" => "select",
            "business_form_id" => "select",
            "revenue" => "text"
        ];
    }
}
