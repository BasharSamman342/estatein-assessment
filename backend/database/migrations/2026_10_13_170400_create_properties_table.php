<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('title_en', 200);
            $table->string('title_ar', 200);
            $table->text('description_en');
            $table->text('description_ar');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('location_en', 200)->index();
            $table->string('location_ar', 200)->index();
            $table->unsignedSmallInteger('bedrooms');
            $table->unsignedSmallInteger('bathrooms');
            $table->unsignedInteger('area_sqft');
            $table->unsignedSmallInteger('year_built');
            $table->string('property_type', 20)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
            $table->index('price');
        });

        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_en', 200)->nullable();
            $table->string('alt_ar', 200)->nullable();
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['property_id', 'is_cover', 'sort_order']);
        });

        Schema::create('property_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('label_en', 120);
            $table->string('label_ar', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['property_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_features');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('properties');
    }
};
