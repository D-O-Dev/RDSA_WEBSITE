<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->string('reservation_phone_hash', 64)->nullable();
            $table->boolean('has_attended_before')->default(false);
            $table->json('previous_edition_ids')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->unique(['edition_id', 'reservation_phone_hash'], 'reservation_edition_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->dropUnique('reservation_edition_phone_unique');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reservation_phone_hash', 'has_attended_before', 'previous_edition_ids', 'reviewed_at', 'review_reason']);
        });
    }
};
