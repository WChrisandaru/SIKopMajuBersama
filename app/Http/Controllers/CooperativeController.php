<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class CooperativeController extends Controller {
 public function login(){return view('login');}
 public function authenticate(Request $r){$c=$r->validate(['email'=>'required|email','password'=>'required']);if(!Auth::attempt($c))return back()->withErrors(['email'=>'Email atau kata sandi tidak sesuai.'])->onlyInput('email');$r->session()->regenerate();return redirect('/dashboard');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/login');}
 public function page(Request $r,string $page,?int $id=null){
 $titles=['dashboard'=>'Ringkasan koperasi','anggota'=>'Anggota','tagihan'=>'Tagihan','pembayaran'=>'Pembayaran','bukti'=>'Bukti pembayaran','simpanan'=>'Simpanan','pinjaman'=>'Pinjaman','arisan'=>'Arisan','arisan-emas'=>'Arisan Emas','buku-kas'=>'Buku Kas','laporan'=>'Laporan','pengguna'=>'Pengguna & akses','audit'=>'Jejak aktivitas','pengaturan'=>'Pengaturan'];abort_unless(isset($titles[$page]),404);
 $r->validate(['period'=>'nullable|date_format:Y-m','q'=>'nullable|string|max:150']);$selectedPeriod=$r->filled('bill')?DB::table('bills')->where('id',$r->query('bill'))->value('period'):null;$period=$r->query('period',$selectedPeriod??'2026-11');$q=$r->query('q','');
 $members=DB::table('members')->where(fn($b)=>$b->where('name','like',"%$q%")->orWhere('number','like',"%$q%"))->get();
 $allMembers=DB::table('members')->get();
 $bills=DB::table('bills')->join('members','members.id','=','bills.member_id')->select('bills.*','members.name','members.number')->where('period',$period)->get();
 $payments=DB::table('payments')->join('bills','bills.id','=','payments.bill_id')->join('members','members.id','=','bills.member_id')->join('users','users.id','=','payments.user_id')->select('payments.*','members.name','members.number','bills.period','users.name as officer')->orderByDesc('payments.id')->get();
 $cash=DB::table('cash_entries')->join('users','users.id','=','cash_entries.user_id')->select('cash_entries.*','users.name as officer')->orderBy('date')->orderBy('cash_entries.id')->get();
 $savings=DB::table('savings')->join('members','members.id','=','savings.member_id')->select('savings.*','members.name')->get();
 $loans=DB::table('loans')->join('members','members.id','=','loans.member_id')->select('loans.*','members.name')->get();
 $member=$id && $page==='anggota'?$allMembers->firstWhere('id',$id):null;
 $receipt=$page==='bukti'?$payments->firstWhere('id',$id):null;
 if(($page==='anggota' && $id && !$member)||($page==='bukti'&&!$receipt))abort(404);
 return view('app',compact('page','titles','members','allMembers','bills','payments','cash','savings','loans','period','q','member','receipt'));
 }
 public function mutate(Request $r,string $action){
 abort_unless(Auth::user()->role==='bendahara',403);
 if($action==='member'){$v=$r->validate(['number'=>['required','max:30',Rule::unique('members')->ignore($r->input('id'))],'name'=>'required|max:150','contact'=>'nullable|max:100','address'=>'nullable|max:1000','status'=>'required|in:Aktif,Nonaktif']);if($r->filled('id')){abort_unless(DB::table('members')->where('id',$r->id)->exists(),404);DB::table('members')->where('id',$r->id)->update($v);}else DB::table('members')->insert($v);}
 elseif($action==='generate'){$v=$r->validate(['period'=>'required|date_format:Y-m','amount'=>'required|integer|min:1|max:100000000']);foreach(DB::table('members')->where('status','Aktif')->get() as $m) DB::table('bills')->insertOrIgnore(['member_id'=>$m->id,'period'=>$v['period'],'amount'=>$v['amount'],'status'=>'Draft']);}
 elseif($action==='finalize'){$r->validate(['period'=>'required|date_format:Y-m']);DB::table('bills')->where('period',$r->period)->where('status','Draft')->update(['status'=>'Final']);}
 elseif($action==='pay'){
 $v=$r->validate(['bill_id'=>'required|exists:bills,id','method'=>'required|in:Tunai,Transfer','date'=>'required|date','amount'=>'required|integer|min:1','verified'=>'accepted']);
 $pid=DB::transaction(function()use($v){$bill=DB::table('bills')->where('id',$v['bill_id'])->first();abort_unless($bill->status==='Final',422,'Tagihan harus final dan belum dibayar.');abort_unless((int)$v['amount']===$bill->amount,422,'Demo hanya mendukung pelunasan penuh sesuai tagihan.');$changed=DB::table('bills')->where('id',$bill->id)->where('status','Final')->update(['status'=>'Lunas']);abort_unless($changed===1,409);
 $pid=DB::table('payments')->insertGetId(['bill_id'=>$bill->id,'user_id'=>Auth::id(),'amount'=>$bill->amount,'method'=>$v['method'],'date'=>$v['date'],'reference'=>'MB-'.strtoupper(bin2hex(random_bytes(5)))]);
 DB::table('cash_entries')->insert(['payment_id'=>$pid,'date'=>$v['date'],'category'=>'Simpanan Wajib','description'=>'Pembayaran tagihan #'.$bill->id,'amount'=>$bill->amount,'user_id'=>Auth::id()]);
 DB::table('savings')->insert(['payment_id'=>$pid,'member_id'=>$bill->member_id,'type'=>'Wajib','date'=>$v['date'],'amount'=>$bill->amount]);$this->audit('Konfirmasi pembayaran #'.$pid);return $pid;});return redirect('/bukti/'.$pid)->with('success','Pembayaran tersimpan. Tagihan, simpanan, dan Buku Kas telah diperbarui.');
 }
 elseif($action==='expense'){$v=$r->validate(['date'=>'required|date','category'=>'required|in:Operasional,Administrasi,Sosial','description'=>'required|max:255','amount'=>'required|integer|min:1|max:100000000']);$v['amount']=-(int)$v['amount'];$v['user_id']=Auth::id();DB::table('cash_entries')->insert($v);}
 else abort(404);
 $this->audit('Perubahan '.$action);return back()->with('success','Data berhasil disimpan.');
 }
 private function audit($action){DB::table('audit_entries')->insert(['user_id'=>Auth::id(),'action'=>$action,'created_at'=>now()]);}
}
