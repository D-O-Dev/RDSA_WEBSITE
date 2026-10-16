<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('participation_id')
                ->constrained('participations')
                ->restrictOnDelete();

            $table->foreignId('wave_number_id')
                ->nullable()
                ->constrained('wave_numbers')
                ->nullOnDelete();

            // Informations déclarées par le participant
            $table->string('transaction_reference', 150);
            $table->unsignedBigInteger('amount_declared');
            $table->dateTime('payment_date_declared')->nullable();

            // Copie du numéro choisi au moment de la déclaration
            $table->string('wave_phone_snapshot', 30);

            // Vérification administrative
            $table->string('status', 30)->default('pending');

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['participation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};