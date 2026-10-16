<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();

            // Identification de l'édition
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->unsignedSmallInteger('number')->unique();

            // Informations de l'événement
            $table->string('theme', 255);
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->string('venue', 255);
            $table->string('venue_details', 255)->nullable();

            // Tarification
            $table->unsignedInteger('base_price');

            // Gestion de l'édition
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('starts_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};