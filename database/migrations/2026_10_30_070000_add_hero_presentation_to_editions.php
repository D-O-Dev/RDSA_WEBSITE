<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('editions', fn (Blueprint $t) => $t->json('hero_settings')->nullable()); }
    public function down(): void { Schema::table('editions', fn (Blueprint $t) => $t->dropColumn('hero_settings')); }
};
