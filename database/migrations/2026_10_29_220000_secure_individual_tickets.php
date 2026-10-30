<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('tickets')->select('participation_id')->groupBy('participation_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Plusieurs billets existent pour une participation. Vérifier ces données avant la migration.');
        }
        Schema::table('tickets', function (Blueprint $table) {
            $table->unique('participation_id');
            $table->text('access_token')->nullable();
            $table->string('access_token_hash', 64)->nullable()->unique();
            $table->text('qr_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['participation_id']);
            $table->dropUnique(['access_token_hash']);
            $table->dropColumn(['access_token', 'access_token_hash', 'qr_token']);
        });
    }
};
