<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BigdataResumeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'import_datasource_id' => $this->import_datasource_id,
            'potention_count'      => (int) $this->potention_count,
            'potention_sum'        => number_format((float) $this->potention_sum, 2, ',', '.'),

            'non_verified_count'   => (int) $this->non_verified_count,
            'non_verified_sum'     => number_format((float) $this->non_verified_sum, 2, ',', '.'),

            'verified_count'       => (int) $this->verified_count,
            'verified_sum'         => number_format((float) $this->verified_sum, 2, ',', '.'),

            'business_count'       => (int) $this->business_count,
            'business_sum'         => number_format((float) $this->business_sum, 2, ',', '.'),

            'non_business_count'   => (int) $this->non_business_count,
            'non_business_sum'     => number_format((float) $this->non_business_sum, 2, ',', '.'),

            'spatial_count'        => (int) $this->spatial_count,
            'spatial_sum'          => number_format((float) $this->spatial_sum, 2, ',', '.'),
            
            'issuance_realization_pbg_count'        => (int) $this->issuance_realization_pbg_count,
            'issuance_realization_pbg_sum'          => number_format((float) $this->issuance_realization_pbg_sum, 2, ',', '.'),
            
            'waiting_click_dpmptsp_count'        => (int) $this->waiting_click_dpmptsp_count,
            'waiting_click_dpmptsp_sum'          => number_format((float) $this->waiting_click_dpmptsp_sum, 2, ',', '.'),
            
            'process_in_technical_office_count'        => (int) $this->process_in_technical_office_count,
            'process_in_technical_office_sum'          => number_format((float) $this->process_in_technical_office_sum, 2, ',', '.'),
            
            'year'                 => $this->year,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
