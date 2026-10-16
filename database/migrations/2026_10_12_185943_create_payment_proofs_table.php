<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_proofs')) {
            Schema::create('payment_proofs', function (Blueprint $table) {
                $table->id();

                $table->foreignId('payment_id')
                    ->constrained('payments')
                    ->restrictOnDelete();

                $table->string('disk', 50)->default('local');
                $table->string('file_path', 500);
                $table->string('original_name')->nullable();
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('size_bytes');

                $table->timestamps();
            });

            return;
        }

        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->index('payment_id', 'payment_proofs_payment_id_index');

            $table->foreign('payment_id', 'payment_proofs_payment_id_foreign')
                ->references('id')
                ->on('payments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};