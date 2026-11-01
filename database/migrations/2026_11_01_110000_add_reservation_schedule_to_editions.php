<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            $table->dateTime('reservations_open_at')->nullable();
            $table->dateTime('reservations_close_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('editions', fn (Blueprint $table) => $table->dropColumn(['reservations_open_at', 'reservations_close_at', 'capacity']));
    }
};
