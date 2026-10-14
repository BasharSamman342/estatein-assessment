<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->text('quote_en');
            $table->text('quote_ar');
            $table->string('author_name_en', 120);
            $table->string('author_name_ar', 120);
            $table->string('role_en', 120)->nullable();
            $table->string('role_ar', 120)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('avatar_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
