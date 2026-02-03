<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('title');
            $table->string('destination');
            $table->text('description')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery_images')->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');
            $table->decimal('discount_price', 10, 2)->nullable();

            // Duration & Dates
            $table->integer('duration_days');
            $table->integer('duration_nights');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Tour Details
            $table->string('difficulty_level'); // Easy, Moderate, Challenging, Extreme
            $table->integer('max_group_size')->default(15);
            $table->integer('min_group_size')->default(1);
            $table->string('tour_type'); // Adventure, Cultural, Wildlife, Pilgrimage, etc.

            // Inclusions & Exclusions
            $table->json('inclusions')->nullable(); // Accommodation, Meals, Guide, etc.
            $table->json('exclusions')->nullable(); // Flights, Personal expenses, etc.

            // Accommodation
            $table->json('accommodation_details')->nullable(); // Hotel names, types, ratings

            // Meals
            $table->json('meal_plan')->nullable(); // Breakfast, Lunch, Dinner details

            // Itinerary
            $table->json('itinerary')->nullable(); // Day-by-day schedule

            // Guide & Support
            $table->boolean('guide_included')->default(true);
            $table->string('guide_language')->default('English');
            $table->boolean('porter_included')->default(false);

            // Requirements
            $table->text('requirements')->nullable(); // Fitness level, documents needed
            $table->text('what_to_bring')->nullable();

            // Status & Features
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_popular')->default(false);

            // Booking
            $table->integer('available_slots')->default(0);
            $table->boolean('instant_booking')->default(false);

            // SEO & Meta
            $table->string('slug')->unique()->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
