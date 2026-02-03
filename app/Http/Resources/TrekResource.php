<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrekResource extends JsonResource
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
            'title' => $this->title,
            'location' => $this->location,
            'price' => $this->price,
            'duration' => $this->duration,
            'difficulty' => $this->difficulty,
            'type' => $this->type,
            'distance_km' => $this->distance_km,
            'description' => $this->description,
            'images' => $this->images,
            'image_urls' => $this->images ? array_map(fn($img) => url('storage/' . $img), $this->images) : [],
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'currency' => $this->currency,
            'data_type' => $this->data_type,
            'trek_days' => $this->trek_days,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}