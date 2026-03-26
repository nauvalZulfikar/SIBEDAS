<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Advertisement
 *
 * @property $id
 * @property $created_at
 * @property $updated_at
 * @property $no
 * @property $business_name
 * @property $npwpd
 * @property $advertisement_type
 * @property $advertisement_content
 * @property $business_address
 * @property $advertisement_location
 * @property $village_code
 * @property $district_code
 * @property $length
 * @property $width
 * @property $viewing_angle
 * @property $face
 * @property $area
 * @property $angle
 * @property $contact
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Advertisement extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['no', 'business_name', 'npwpd', 'advertisement_type', 'advertisement_content', 'business_address', 'advertisement_location', 'village_code', 'district_code', 'length', 'width', 'viewing_angle', 'face', 'area', 'angle', 'contact'];


}
