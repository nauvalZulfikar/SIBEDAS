<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'url' => $this->url,
            'sort_order' => $this->sort_order,
            'parent' => $this->parent ? new MenuResource($this->parent) : null,
            'children' => $this->when($this->relationLoaded('children'), function () {
                return $this->children->sortBy('sort_order')->map(function ($child) {
                    return new MenuResource($child);
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
