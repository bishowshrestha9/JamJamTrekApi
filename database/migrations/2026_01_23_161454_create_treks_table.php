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
        Schema::create('treks', function (Blueprint $table) {
            $table->id();
            $table->enum('data_type', ['trek', 'package']);
            $table->string('title');
            $table->string('location');
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('duration');
            $table->string('difficulty');
            $table->string('type');
            $table->float('distance_km');
            $table->text('description')->nullable();
            $table->string('featured_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('trek_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treks');
    }
};