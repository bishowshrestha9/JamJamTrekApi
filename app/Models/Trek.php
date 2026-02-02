<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trek extends Model
{
    protected $fillable = [
        'data_type',
        'title',
        'location',
        'price',
        'currency',
        'is_active',
        'duration',
        'difficulty',
        'type',
        'distance_km',
        'description',
        'featured_image',
        'is_featured',
        'trek_days',
    ];

    protected $casts = [
        'trek_days' => 'array',
    ];
}