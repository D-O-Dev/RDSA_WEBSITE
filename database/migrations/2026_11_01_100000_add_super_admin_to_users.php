<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_super_admin')->default(false));
        // Le premier administrateur actif devient propriétaire ; aucun compte supplémentaire n'est créé.
        $owner = DB::table('users')->where('is_admin', true)->where('is_active', true)->orderBy('id')->value('id');
        if ($owner) {
            DB::table('users')->where('id', $owner)->update(['is_super_admin' => true]);
        }
        Schema::table('admin_actions', fn (Blueprint $table) => $table->string('reason', 1000)->nullable());
        Schema::table('admin_actions', fn (Blueprint $table) => $table->json('details')->nullable());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_super_admin'));
        Schema::table('admin_actions', fn (Blueprint $table) => $table->dropColumn('reason'));
        Schema::table('admin_actions', fn (Blueprint $table) => $table->dropColumn('details'));
    }
};
