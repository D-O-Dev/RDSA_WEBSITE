<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_items', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('action', 100)->index();
            $table->string('target', 100)->nullable();
            $table->timestamp('created_at')->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::table('media_items', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
