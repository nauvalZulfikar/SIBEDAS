<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestAssignmentResouce extends JsonResource
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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'owner_name' => $this->owner_name,
            'application_type' => $this->application_type,
            'application_type_name' => $this->application_type_name,
            'condition' => $this->condition,
            'registration_number' => $this->registration_number,
            'document_number' => $this->document_number,
            'address' => $this->address,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'slf_status' => $this->slf_status,
            'slf_status_name' => $this->slf_status_name,
            'function_type' => $this->function_type,
            'consultation_type' => $this->consultation_type,
            'due_date' => $this->due_date,
            'land_certificate_phase' => $this->land_certificate_phase,
            'task_created_at' => $this->task_created_at,
            'attachment_berita_acara' => $this->attachments
                ->where('pbg_type', 'berita_acara')
                ->sortByDesc('created_at')
                ->first(),
            'attachment_bukti_bayar' => $this->attachments
                ->where('pbg_type', 'bukti_bayar')
                ->sortByDesc('created_at')
                ->first(),
            'usulan_retribusi' => $this->usulan_retribusi,
            'pbg_task_retributions' => $this->pbg_task_retributions,
            'pbg_task_detail' => $this->pbg_task_detail,
            'pbg_status' => $this->pbg_status,
        ];
    }
}
