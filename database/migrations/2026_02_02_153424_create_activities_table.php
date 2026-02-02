<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location');
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('duration'); // e.g., "2 Hours", "Full Day"
            $table->string('difficulty'); // Easy, Moderate, Challenging
            $table->string('category'); // Aerial, Water, Adventure, Cultural, Wellness
            $table->integer('min_age')->nullable(); // Minimum age requirement
            $table->integer('max_participants')->nullable(); // Maximum group size
            $table->text('description')->nullable();
            $table->text('inclusions')->nullable(); // What's included
            $table->text('requirements')->nullable(); // Prerequisites/requirements
            $table->string('featured_image')->nullable();
            $table->json('gallery_images')->nullable(); // Additional images
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('season')->nullable(); // Best season
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
