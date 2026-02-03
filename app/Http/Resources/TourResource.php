<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
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
            'destination' => $this->destination,
            'description' => $this->description,
            'featured_image' => $this->featured_image ? url('storage/' . $this->featured_image) : null,
            'gallery_images' => $this->gallery_images ? array_map(function ($image) {
                return url('storage/' . $image);
            }, $this->gallery_images) : [],

            'price' => (float) $this->price,
            'currency' => $this->currency,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'has_discount' => $this->discount_price ? true : false,

            'duration' => [
                'days' => $this->duration_days,
                'nights' => $this->duration_nights,
                'formatted' => $this->duration_days . ' Days / ' . $this->duration_nights . ' Nights',
            ],

            'dates' => [
                'start_date' => $this->start_date?->format('Y-m-d'),
                'end_date' => $this->end_date?->format('Y-m-d'),
            ],

            'difficulty_level' => $this->difficulty_level,
            'group_size' => [
                'min' => $this->min_group_size,
                'max' => $this->max_group_size,
            ],
            'tour_type' => $this->tour_type,

            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],

            'accommodation_details' => $this->accommodation_details ?? [],
            'meal_plan' => $this->meal_plan ?? [],
            'itinerary' => $this->itinerary ?? [],

            'guide' => [
                'included' => $this->guide_included,
                'language' => $this->guide_language,
            ],
            'porter_included' => $this->porter_included,

            'requirements' => $this->requirements,
            'what_to_bring' => $this->what_to_bring,

            'status' => [
                'is_active' => $this->is_active,
                'is_featured' => $this->is_featured,
                'is_popular' => $this->is_popular,
            ],

            'booking' => [
                'available_slots' => $this->available_slots,
                'instant_booking' => $this->instant_booking,
            ],

            'slug' => $this->slug,
            'meta_description' => $this->meta_description,
            'tags' => $this->tags ?? [],

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
