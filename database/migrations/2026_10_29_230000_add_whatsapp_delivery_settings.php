<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participations', fn (Blueprint $table) => $table->boolean('whatsapp_opt_in')->default(false));
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('phone_number_id')->nullable();
            $table->string('api_version', 20)->default('v24.0');
            $table->string('template_name')->nullable();
            $table->string('template_language', 20)->default('fr');
            $table->string('public_base_url', 500)->nullable();
            $table->text('access_token')->nullable();
            $table->text('app_secret')->nullable();
            $table->text('verify_token')->nullable();
            $table->timestamps();
        });
        Schema::create('whatsapp_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('provider_message_id')->nullable()->unique();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('provider_status_timestamp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_deliveries');
        Schema::dropIfExists('whatsapp_settings');
        Schema::table('participations', fn (Blueprint $table) => $table->dropColumn('whatsapp_opt_in'));
    }
};
