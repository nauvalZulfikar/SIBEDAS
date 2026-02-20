<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Tourism;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TourismController extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        $permissions = $this->permissions[$menuId] ?? []; // Avoid undefined index error

        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;
        return view('data.tourisms.index', compact('creator', 'updater', 'destroyer', 'menuId'));
    }

    /**
     * show the form for creating a new rsource.
     */
    public function bulkCreate(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        return view('data.tourisms.form-upload', compact('menuId'));
    }

    /**
     * Show th form for creating a new resource
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'Pariwisata';
        $subtitle = 'Create Data';

        // Mengambil data untuk dropdown
        $dropdownOptions = [
            'village_name' => DB::table('villages')->orderBy('village_name')->pluck('village_name', 'village_code'),
            'district_name' => DB::table('districts')->orderBy('district_name')->pluck('district_name', 'district_code')
        ];

        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/tourisms');

        return view('data.tourisms.form', compact('title', 'subtitle', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions', 'menuId'));
    }

    /**
     * show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $menuId = $request->query('menu_id', 0);
        $title = 'Pariwisata';
        $subtitle = 'Update Data';

        $modelInstance = Tourism::find($id);
        // Pastikan model ditemukan
        if (!$modelInstance) {
            return redirect()->route('web-tourisms.index') ->with('error', 'Pariwisata tidak ditemukan');
        }

        // Mengambil dan memetakan village_name dan district_name
        $village = DB::table('villages')->where('village_code', $modelInstance->village_code)->first();
        $modelInstance->village_name = $village ? $village->village_name : null;

        $district = DB::table('districts')->where('district_code', $modelInstance->district_code)->first();
        $modelInstance->district_name = $district ? $district->district_name : null;

        $dropdownOptions = [
            'village_name' => DB::table('villages')->orderBy('village_name')->pluck('village_name', 'village_code'),
            'district_name' => DB::table('districts')->orderBy('district_name')->pluck('district_name', 'district_code')
        ];

        $fields = $this->getFields();
        $fieldTypes = $this->getFieldTypes();

        $apiUrl = url('/api/tourisms');

        return view('data.tourisms.form', compact('title', 'subtitle', 'modelInstance', 'fields', 'fieldTypes', 'apiUrl', 'dropdownOptions', 'menuId'));
    }

    private function getFields()
    {
        return [
            "project_id" => "ID Proyek",
            "project_type_id" => "Jenis Proyek",
            "nib" => "NIB",
            "business_name" => "Nama Perusahaan",
            "oss_publication_date" => "Tanggal Terbit OSS",
            "investment_status_description" => "Uraian Status Penanaman Modal",
            "business_form" => "Uraian Jenis Perusahaan",
            "project_risk" => "Risiko Proyek",
            "project_name" => "Nama Proyek",
            "business_scale" => "Uraian Skala Usaha",
            "business_address" => "Alamat Usaha",
            "district_name" => "Kecamatan",
            "village_name" => "Desa",
            "longitude" => "Longitude",
            "latitude" => "Latitude",
            "project_submission_date" => "Tanggal Pengajuan Project",
            "kbli" => "KBLI",
            "kbli_title" => "Judul KBLI",
            "supervisory_sector" => "Sektor Pembina",
            "user_name" => "Nama User",
            "email" => "Email",
            "contact" => "Kontak",
            "land_area_in_m2" => "Luas Tanah (m2)",
            "investment_amount" => "Jumlah Investasi",
            "tki" => "TKI",
        ];
    }

    private function getFieldTypes()
    {
        return [
            "project_id" => "text",
            "project_type_id" => "text",
            "nib" => "text",
            "business_name" => "text",
            "oss_publication_date" => "date",
            "investment_status_description" => "text",
            "business_form" => "text",
            "project_risk" => "text",
            "project_name" => "text",
            "business_scale" => "text",
            "business_address" => "text",
            "district_name" => "combobox",
            "village_name" => "combobox",
            "longitude" => "text",
            "latitude" => "text",
            "project_submission_date" => "date",
            "kbli" => "text",
            "kbli_title" => "text",
            "supervisory_sector" => "text",
            "user_name" => "text",
            "email" => "text",
            "contact" => "text",
            "land_area_in_m2" => "text",
            "investment_amount" => "text",
            "tki" => "text",
        ];
    }
}