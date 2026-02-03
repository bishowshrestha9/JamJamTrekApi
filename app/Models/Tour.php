<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tour extends Model
{
    protected $fillable = [
        'title',
        'destination',
        'description',
        'featured_image',
        'gallery_images',
        'price',
        'currency',
        'discount_price',
        'duration_days',
        'duration_nights',
        'start_date',
        'end_date',
        'difficulty_level',
        'max_group_size',
        'min_group_size',
        'tour_type',
        'inclusions',
        'exclusions',
        'accommodation_details',
        'meal_plan',
        'itinerary',
        'guide_included',
        'guide_language',
        'porter_included',
        'requirements',
        'what_to_bring',
        'is_active',
        'is_featured',
        'is_popular',
        'available_slots',
        'instant_booking',
        'slug',
        'meta_description',
        'tags',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'inclusions' => 'array',
        'exclusions' => 'array',
        'accommodation_details' => 'array',
        'meal_plan' => 'array',
        'itinerary' => 'array',
        'tags' => 'array',
        'guide_included' => 'boolean',
        'porter_included' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'instant_booking' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tour) {
            if (empty($tour->slug)) {
                $tour->slug = Str::slug($tour->title);
            }
        });

        static::updating(function ($tour) {
            if ($tour->isDirty('title') && empty($tour->slug)) {
                $tour->slug = Str::slug($tour->title);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
