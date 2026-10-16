<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('participation_id')
                ->constrained('participations')
                ->restrictOnDelete();

            // Numéro du ticket dans la participation
            $table->unsignedSmallInteger('ticket_number');

            // Référence publique et jeton QR
            $table->string('reference', 50)->unique();
            $table->string('qr_token_hash', 64)->unique();

            $table->string('status', 30)->default('pending');

            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            $table->unique([
                'participation_id',
                'ticket_number'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};