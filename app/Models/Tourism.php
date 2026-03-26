<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Tourism
 *
 * @property $id
 * @property $created_at
 * @property $updated_at
 * @property $project_id
 * @property $project_type_id
 * @property $nib
 * @property $business_name
 * @property $oss_publication_date
 * @property $investment_status_description
 * @property $business_form
 * @property $project_risk
 * @property $project_name
 * @property $business_scale
 * @property $business_address
 * @property $district_code
 * @property $village_code
 * @property $longitude
 * @property $latitude
 * @property $project_submission_date
 * @property $kbli
 * @property $kbli_title
 * @property $supervisory_sector
 * @property $user_name
 * @property $email
 * @property $contact
 * @property $land_area_in_m2
 * @property $investment_amount
 * @property $tki
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Tourism extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['project_id', 'project_type_id', 'nib', 'business_name', 'oss_publication_date', 'investment_status_description', 'business_form', 'project_risk', 'project_name', 'business_scale', 'business_address', 'district_code', 'village_code', 'longitude', 'latitude', 'project_submission_date', 'kbli', 'kbli_title', 'supervisory_sector', 'user_name', 'email', 'contact', 'land_area_in_m2', 'investment_amount', 'tki'];


}
