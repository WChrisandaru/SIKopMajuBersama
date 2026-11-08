<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique();
            foreach (['mother_name', 'bank_name', 'account_holder', 'emergency_name', 'emergency_relationship'] as $column) {
                $table->string($column, 150)->nullable();
            }
            $table->string('account_number', 34)->nullable();
            $table->string('emergency_phone', 30)->nullable();
        });
        Schema::create('member_number_sequences', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        $highest = 0;
        foreach (DB::table('members')->pluck('number') as $number) {
            if (preg_match('/^MB-(\d+)$/', $number, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }
        DB::table('member_number_sequences')->insert(['id' => 1, 'last_number' => $highest]);
    }

    public function down(): void
    {
        Schema::dropIfExists('member_number_sequences');
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn(['nik', 'mother_name', 'bank_name', 'account_holder', 'account_number', 'emergency_name', 'emergency_phone', 'emergency_relationship']);
        });
    }
};
