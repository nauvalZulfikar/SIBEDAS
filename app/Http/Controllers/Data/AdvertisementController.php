<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdvertisementController extends Controller
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

        return view('data.advertisements.index', compact('creator', 'updater', 'destroyer','menuId'));
    }

    /**
     * Show the form for uploading a file.
     */
    public function bulkCreate()
    {
        // Mengembalikan view form-upload
        return view('data.advertisements.form-upload');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'Advertisement';
        $subtitle = 'Create Data';

        // Mengambil data untuk dropdown
        $dropdownOptions = [
            'village_name' => DB::table('villages')->orderBy('village_name')->pluck('village_name', 'village_code'),
            'district_name' => DB::table('districts')->orderBy('district_name')->pluck('district_name', 'district_code'),
        ];

        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/advertisements');
        
        // $route = 'advertisements.create';
        // info("AdvertisementController@edit diakses dengan ID: $title");
        return view('data.advertisements.form', compact('title', 'subtitle', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions','menuId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $menuId = $request->query('menu_id', 0);
        info("AdvertisementController@edit diakses dengan ID: $id");
        $title = 'Advertisement';
        $subtitle = 'Update Data';
        $modelInstance = Advertisement::find($id);
        // Pastikan model ditemukan
        if (!$modelInstance) {
            info("AdvertisementController@edit: Model tidak ditemukan.");
            return redirect()->route('web.advertisements.index')->with('error', 'Advertisement not found');
        }

        // Mengambil dan memetakan village_name dan district_name
        $village = DB::table('villages')->where('village_code', $modelInstance->village_code)->first();
        $modelInstance->village_name = $village ? $village->village_name : null;

        $district = DB::table('districts')->where('district_code', $modelInstance->district_code)->first();
        $modelInstance->district_name = $district ? $district->district_name : null;

        // Mengambil data untuk dropdown
        $dropdownOptions = [
            'village_name' => DB::table('villages')->orderBy('village_name')->pluck('village_name', 'village_code'),
            'district_name' => DB::table('districts')->orderBy('district_name')->pluck('district_name', 'district_code'),
        ];

        info("AdvertisementController@edit diakses dengan Model Instance: $modelInstance");
        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/advertisements');

        // $route = 'advertisements.update'; // Menggunakan route update untuk form edit
        // info("AdvertisementController@edit diakses dengan route: $route");
        return view('data.advertisements.form', compact('title', 'subtitle', 'modelInstance', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions', 'menuId'));
    }

    private function getFields()
    {
        return [
            "no" => "No",
            "business_name" => "Nama Wajib Pajak",
            "npwpd" => "NPWPD",
            "advertisement_type" => "Jenis Reklame",
            "advertisement_content" => "Isi Reklame",
            "business_address" => "Alamat Wajib Pajak",
            "advertisement_location" => "Lokasi Reklame",
            "district_name" => "Kecamatan",
            "village_name" => "Desa",
            "length" => "Panjang",
            "width" => "Lebar",
            "viewing_angle" => "Sudut Pandang",
            "face" => "Muka",
            "area" => "Luas",
            "angle" => "Sudut",
            "contact" => "Kontak",
        ];
    }

    private function getFieldTypes()
    {
        return [
            "no" => "text",
            "business_name" => "text",
            "npwpd" => "text",
            "advertisement_type" => "text",
            "advertisement_content" => "textarea",
            "business_address" => "text",
            "advertisement_location" => "text",
            "village_name" => "combobox",
            "district_name" => "combobox",
            "length" => "text",
            "width" => "text",
            "viewing_angle" => "text",
            "face" => "text",
            "area" => "text",
            "angle" => "text",
            "contact" => "text",
        ];
    }
}
