<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('album_name', 100)->default('RDSA');
            $table->string('title', 150);
            $table->string('kind', 10)->default('photo');
            $table->string('disk', 20)->default('assets');
            $table->string('file_path', 500)->nullable();
            $table->string('video_url', 1000)->nullable();
            $table->string('cover_path', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_published', 'sort_order']);
        });
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->uuid('submission_token')->unique();
            $table->string('name', 150);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('subject', 150);
            $table->text('body');
            $table->string('status', 20)->default('new')->index();
            $table->text('access_token');
            $table->string('access_token_hash', 64);
            $table->timestamps();
        });
        Schema::create('contact_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_type', 10);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_replies');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('media_items');
    }
};
