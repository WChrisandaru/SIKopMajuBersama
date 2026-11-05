<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
 public function run(): void {
 foreach(['bendahara'=>'Bendahara Demo','ketua'=>'Ketua Demo'] as $role=>$name) DB::table('users')->updateOrInsert(['email'=>$role.'@koperasi.test'],['name'=>$name,'password'=>Hash::make('KoperasiDemo2026!'),'role'=>$role,'created_at'=>now(),'updated_at'=>now()]);
 if(DB::table('members')->exists()) return;
 $uid=DB::table('users')->where('role','bendahara')->value('id');
 foreach(['Anindya Putri','Bima Pratama','Citra Lestari','Damar Wicaksono','Elisa Kartika','Farhan Nugraha','Gita Maharani','Hendra Saputra','Intan Permata','Joko Santoso','Kirana Dewi','Laras Wulandari'] as $i=>$name){
 $id=DB::table('members')->insertGetId(['number'=>sprintf('MB-%03d',$i+1),'name'=>$name,'contact'=>'080000'.sprintf('%04d',$i+1),'address'=>'Alamat contoh, Blok '.chr(65+$i),'status'=>'Aktif']);
 DB::table('bills')->insert(['member_id'=>$id,'period'=>'2026-11','amount'=>100000,'status'=>'Final']);
 DB::table('savings')->insert(['member_id'=>$id,'type'=>'Pokok','date'=>'2026-01-01','amount'=>500000]);
 DB::table('savings')->insert(['member_id'=>$id,'type'=>'Sukarela','date'=>'2026-10-01','amount'=>($i+1)*250000]);
 if($i<5)DB::table('loans')->insert(['member_id'=>$id,'type'=>$i%2?'Sebrakan':'Reguler','principal'=>5000000,'balance'=>3000000,'due'=>$i<2?'2026-10-25':'2026-12-25','status'=>'Aktif']);
 }
 DB::table('cash_entries')->insert(['date'=>'2026-10-01','category'=>'Saldo awal demo','description'=>'Saldo awal ilustratif; bukan data koperasi','amount'=>25000000,'user_id'=>$uid]);
 }
}
