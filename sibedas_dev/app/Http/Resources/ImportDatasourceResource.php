<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportDatasourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $startTime = $this->start_time ? Carbon::parse($this->start_time) : null;
        $finishTime = $this->finish_time ? Carbon::parse($this->finish_time) : null;
        return [
            "id"=> $this->id,
            "message" => $this->message,
            "response_body" => $this->response_body,
            "status" => $this->status,
            "start_time" => $startTime ? $startTime->toDateTimeString() : null,
            "duration" => ($startTime && $finishTime) 
                ? $finishTime->diff($startTime)->format('%H:%I:%S') 
                : null,
            "finish_time" => $finishTime ? $finishTime->toDateTimeString() : null,
            "created_at" => $this->created_at->toDateTimeString(),
            "updated_at" => $this->updated_at->toDateTimeString(),
            "failed_uuid" => $this->failed_uuid
        ];
    }
}
