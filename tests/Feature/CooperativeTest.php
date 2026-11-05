<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
class CooperativeTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed();}
 public function test_all_pages_render_for_both_roles():void{foreach(['bendahara','ketua'] as $role){$this->actingAs(User::where('role',$role)->first());foreach(['dashboard','anggota','anggota/1','tagihan','pembayaran','simpanan','pinjaman','arisan','arisan-emas','buku-kas','laporan','pengguna','audit','pengaturan'] as $p)$this->get('/'.$p)->assertOk();}}
 public function test_payment_updates_all_ledgers_and_cannot_be_duplicated():void{
 $this->actingAs(User::where('role','bendahara')->first());$data=['bill_id'=>1,'method'=>'Tunai','date'=>'2026-11-02','amount'=>100000,'verified'=>1];
 $this->post('/action/pay',$data)->assertRedirect('/bukti/1');$this->assertDatabaseHas('bills',['id'=>1,'status'=>'Lunas']);$this->assertDatabaseHas('savings',['payment_id'=>1,'amount'=>100000]);$this->assertDatabaseHas('cash_entries',['payment_id'=>1,'amount'=>100000]);$this->get('/bukti/1')->assertOk()->assertSee('100.000');$this->post('/action/pay',$data)->assertStatus(422);$this->assertDatabaseCount('payments',1);
 }
 public function test_ketua_cannot_mutate():void{$this->actingAs(User::where('role','ketua')->first());foreach(['pay','member','generate','finalize','expense'] as $a)$this->post('/action/'.$a,[])->assertForbidden();}
 public function test_wrong_amount_and_draft_cannot_be_paid():void{$this->actingAs(User::where('role','bendahara')->first());$data=['bill_id'=>1,'method'=>'Tunai','date'=>'2026-11-02','amount'=>1,'verified'=>1];$this->post('/action/pay',$data)->assertStatus(422);DB::table('bills')->where('id',1)->update(['status'=>'Draft']);$data['amount']=100000;$this->post('/action/pay',$data)->assertStatus(422);$this->assertDatabaseCount('payments',0);}
 public function test_login_and_logout():void{$this->get('/dashboard')->assertRedirect('/login');$this->post('/login',['email'=>'bendahara@koperasi.test','password'=>'KoperasiDemo2026!'])->assertRedirect('/dashboard');$this->assertAuthenticated();$this->post('/logout')->assertRedirect('/login');$this->assertGuest();}
 public function test_generation_is_unique_and_requires_finalization():void{$this->actingAs(User::where('role','bendahara')->first());$this->post('/action/generate',['period'=>'2026-12','amount'=>120000])->assertRedirect();$this->post('/action/generate',['period'=>'2026-12','amount'=>120000])->assertRedirect();$this->assertEquals(12,DB::table('bills')->where('period','2026-12')->count());$this->post('/action/finalize',['period'=>'2026-12'])->assertRedirect();$this->assertEquals(12,DB::table('bills')->where('period','2026-12')->where('status','Final')->count());}
}
