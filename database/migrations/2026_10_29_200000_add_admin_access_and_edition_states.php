<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::table('editions', function (Blueprint $table) {
            $table->boolean('is_published')->default(true);
            $table->boolean('is_archived')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('editions', fn (Blueprint $table) => $table->dropColumn(['is_published', 'is_archived']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
