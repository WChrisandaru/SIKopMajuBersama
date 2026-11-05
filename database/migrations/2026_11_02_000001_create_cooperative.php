<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
 Schema::table('users',fn(Blueprint $t)=>$t->string('role')->default('ketua'));
 Schema::create('members',function(Blueprint $t){$t->id();$t->string('number')->unique();$t->string('name');$t->string('contact')->nullable();$t->text('address')->nullable();$t->string('status')->default('Aktif');});
 Schema::create('bills',function(Blueprint $t){$t->id();$t->foreignId('member_id')->constrained('members');$t->string('period');$t->unsignedBigInteger('amount');$t->string('status')->default('Draft');$t->unique(['member_id','period']);});
 Schema::create('payments',function(Blueprint $t){$t->id();$t->foreignId('bill_id')->unique()->constrained('bills');$t->foreignId('user_id')->constrained();$t->unsignedBigInteger('amount');$t->string('method');$t->date('date');$t->string('reference')->unique();});
 Schema::create('cash_entries',function(Blueprint $t){$t->id();$t->foreignId('payment_id')->nullable()->unique()->constrained('payments');$t->date('date');$t->string('category');$t->string('description');$t->bigInteger('amount');$t->foreignId('user_id')->constrained();});
 Schema::create('savings',function(Blueprint $t){$t->id();$t->foreignId('member_id')->constrained('members');$t->foreignId('payment_id')->nullable()->unique()->constrained('payments');$t->string('type');$t->date('date');$t->bigInteger('amount');});
 Schema::create('loans',function(Blueprint $t){$t->id();$t->foreignId('member_id')->constrained('members');$t->string('type');$t->unsignedBigInteger('principal');$t->unsignedBigInteger('balance');$t->date('due');$t->string('status');});
 Schema::create('audit_entries',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->string('action');$t->timestamp('created_at');});
 }
 public function down(): void {foreach(['audit_entries','loans','savings','cash_entries','payments','bills','members'] as $n) Schema::dropIfExists($n);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));}
};
