<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bill_id')->constrained('bills');
            $t->string('category');
            $t->unsignedBigInteger('amount');
            $t->date('due_date')->nullable();
            $t->foreignId('loan_id')->nullable()->constrained('loans');
            $t->boolean('paid')->default(false);
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->dropUnique(['bill_id']);
            $t->timestamp('recorded_at')->nullable();
        });
        Schema::table('cash_entries', fn (Blueprint $t) => $t->dropUnique(['payment_id']));
        Schema::table('savings', fn (Blueprint $t) => $t->dropUnique(['payment_id']));
        Schema::create('payment_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained('payments');
            $t->foreignId('bill_item_id')->unique()->constrained('bill_items');
            $t->unsignedBigInteger('amount');
        });
        foreach (DB::table('bills')->orderBy('id')->cursor() as $bill) {
            $itemId = DB::table('bill_items')->insertGetId(['bill_id' => $bill->id, 'category' => 'Simpanan Wajib', 'amount' => $bill->amount, 'paid' => $bill->status === 'Lunas']);
            $payment = DB::table('payments')->where('bill_id', $bill->id)->first();
            if ($payment) {
                DB::table('payment_items')->insert(['payment_id' => $payment->id, 'bill_item_id' => $itemId, 'amount' => $payment->amount]);
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi rincian pembayaran tidak dapat dibalik otomatis karena dapat menghilangkan alokasi transaksi.');
    }
};
