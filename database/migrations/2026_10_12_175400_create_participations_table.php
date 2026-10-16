<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('participant_id')
                ->constrained('participants')
                ->restrictOnDelete();

            $table->foreignId('edition_id')
                ->constrained('editions')
                ->restrictOnDelete();

            // Référence publique de la participation
            $table->string('reference', 40)->unique();

            // Quantité et tarification
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedBigInteger('base_amount');
            $table->unsignedBigInteger('extra_contribution')->default(0);
            $table->unsignedBigInteger('total_expected');

            // État du traitement
            $table->string('status', 30)->default('pending');

            $table->timestamps();

            $table->index(['edition_id', 'status']);
            $table->index(['participant_id', 'edition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};