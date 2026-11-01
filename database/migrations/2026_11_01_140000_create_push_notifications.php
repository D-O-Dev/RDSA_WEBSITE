<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('endpoint_hash', 64)->unique();
            $table->text('subscription');
            $table->string('delete_token_hash', 64);
            $table->timestamps();
        });
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('push_subscriptions')->cascadeOnDelete();
            $table->string('event_key', 100);
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
            $table->unique(['subscription_id', 'event_key']);
        });
        Schema::table('site_updates', fn (Blueprint $table) => $table->timestamp('push_notified_at')->nullable());
        DB::table('site_updates')->update(['push_notified_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
        Schema::dropIfExists('push_subscriptions');
        Schema::table('site_updates', fn (Blueprint $table) => $table->dropColumn('push_notified_at'));
    }
};
