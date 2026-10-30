<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('verified_transaction_key', 150)->nullable()->unique();
        });
        DB::table('payments')->where('status', 'verified')->orderBy('id')->each(function ($payment) {
            DB::table('payments')->where('id', $payment->id)->update([
                'verified_transaction_key' => strtoupper(preg_replace('/\s+/u', '', $payment->transaction_reference)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('verified_transaction_key'));
    }
};
