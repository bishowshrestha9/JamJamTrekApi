<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    protected $fillable = ['name', 'email', 'review', 'rating', 'status', 'trek_id'];

    public function trek()
    {
        return $this->belongsTo(Trek::class);
    }
}
