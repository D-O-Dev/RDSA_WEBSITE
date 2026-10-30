<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('participations', 'quantity')) {
            Schema::table('participations', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('participations', 'quantity')) {
            Schema::table('participations', function (Blueprint $table) {
                $table->unsignedSmallInteger('quantity')->default(1);
            });
        }
    }
};
