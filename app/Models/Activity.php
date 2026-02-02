<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'title',
        'location',
        'price',
        'currency',
        'duration',
        'difficulty',
        'category',
        'min_age',
        'max_participants',
        'description',
        'inclusions',
        'requirements',
        'featured_image',
        'gallery_images',
        'is_featured',
        'is_active',
        'season',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];
}
