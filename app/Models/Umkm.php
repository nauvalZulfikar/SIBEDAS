<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Umkm
 *
 * @property $id
 * @property $created_at
 * @property $updated_at
 * @property $business_name
 * @property $business_address
 * @property $business_desc
 * @property $business_contact
 * @property $business_id_number
 * @property $business_scale_id
 * @property $owner_id
 * @property $owner_name
 * @property $owner_address
 * @property $owner_contact
 * @property $business_type
 * @property $business_form_id
 * @property $revenue
 * @property $village_code
 * @property $distric_code
 * @property $number_of_employee
 * @property $land_area
 * @property $permit_status_id
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Umkm extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['business_name', 'business_address', 'business_desc', 'business_contact', 'business_id_number', 'business_scale_id', 'owner_id', 'owner_name', 'owner_address', 'owner_contact', 'business_type', 'business_form_id', 'revenue', 'village_code', 'district_code', 'number_of_employee', 'land_area', 'permit_status_id'];


}
