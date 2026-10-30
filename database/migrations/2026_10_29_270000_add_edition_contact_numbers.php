<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_whatsapp', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('editions', fn (Blueprint $table) => $table->dropColumn(['contact_phone', 'contact_whatsapp']));
    }
};
