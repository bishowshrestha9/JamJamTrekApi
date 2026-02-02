<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'currency' => $this->currency,
            'duration' => $this->duration,
            'difficulty' => $this->difficulty,
            'category' => $this->category,
            'min_age' => $this->min_age,
            'max_participants' => $this->max_participants,
            'description' => $this->description,
            'inclusions' => $this->inclusions,
            'requirements' => $this->requirements,
            'featured_image' => $this->featured_image,
            'featured_image_url' => $this->featured_image ? url('storage/' . $this->featured_image) : null,
            'gallery_images' => $this->gallery_images,
            'gallery_images_urls' => $this->gallery_images ? array_map(function($image) {
                return url('storage/' . $image);
            }, $this->gallery_images) : [],
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'season' => $this->season,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
