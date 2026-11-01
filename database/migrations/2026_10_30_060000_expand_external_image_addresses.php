<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('editions', fn (Blueprint $t) => $t->string('hero_background_path', 2000)->nullable()->change());
        Schema::table('media_items', function (Blueprint $t) {
            $t->string('file_path', 2000)->nullable()->change();
            $t->string('cover_path', 2000)->nullable()->change();
        });
    }
    public function down(): void
    {
        // Conserver les adresses longues existantes lors d'un retour arrière.
    }
};
