<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->unique()
                ->constrained('tickets')
                ->restrictOnDelete();

            $table->foreignId('checked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('checked_in_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};