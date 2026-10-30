<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            $table->string('hero_background_path', 500)->nullable();
            $table->string('hero_landscape_path', 500)->nullable();
            $table->string('hero_portrait_path', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            $table->dropColumn(['hero_background_path', 'hero_landscape_path', 'hero_portrait_path']);
        });
    }
};
