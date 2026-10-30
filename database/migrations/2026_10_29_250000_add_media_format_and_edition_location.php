<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->string('display_format', 20)->default('auto');
        });
        Schema::table('editions', function (Blueprint $table) {
            $table->string('map_url', 2000)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('media_items', fn (Blueprint $table) => $table->dropColumn('display_format'));
        Schema::table('editions', fn (Blueprint $table) => $table->dropColumn(['map_url', 'latitude', 'longitude']));
    }
};
