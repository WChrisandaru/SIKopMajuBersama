<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CooperativeController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function authenticate(Request $r)
    {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($c)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }$r->session()->regenerate();

        return redirect('/dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/login');
    }

    public function page(Request $r, string $page, ?int $id = null)
    {
        $titles = ['dashboard' => 'Ringkasan koperasi', 'anggota' => 'Anggota', 'tagihan' => 'Tagihan', 'pembayaran' => 'Pembayaran', 'bukti' => 'Bukti pembayaran', 'simpanan' => 'Simpanan', 'pinjaman' => 'Pinjaman', 'arisan' => 'Arisan', 'arisan-emas' => 'Arisan Emas', 'buku-kas' => 'Buku Kas', 'laporan' => 'Laporan', 'pengguna' => 'Pengguna & akses', 'audit' => 'Jejak aktivitas', 'pengaturan' => 'Pengaturan'];
        abort_unless(isset($titles[$page]), 404);
        $r->validate(['period' => 'nullable|date_format:Y-m', 'q' => 'nullable|string|max:150']);
        $selectedPeriod = $r->filled('bill') ? DB::table('bills')->where('id', $r->query('bill'))->value('period') : null;
        $period = $r->query('period', $selectedPeriod ?? '2026-11');
        $q = $r->query('q', '');
        $members = DB::table('members')->where(fn ($b) => $b->where('name', 'like', "%$q%")->orWhere('number', 'like', "%$q%"))->get();
        $allMembers = DB::table('members')->get();
        $bills = DB::table('bills')->join('members', 'members.id', '=', 'bills.member_id')->select('bills.*', 'members.name', 'members.number')->where('period', $period)->get();
        $payments = DB::table('payments')->join('bills', 'bills.id', '=', 'payments.bill_id')->join('members', 'members.id', '=', 'bills.member_id')->join('users', 'users.id', '=', 'payments.user_id')->select('payments.*', 'members.name', 'members.number', 'bills.period', 'users.name as officer')->orderByDesc('payments.id')->get();
        $cash = DB::table('cash_entries')->join('users', 'users.id', '=', 'cash_entries.user_id')->select('cash_entries.*', 'users.name as officer')->orderBy('date')->orderBy('cash_entries.id')->get();
        $savings = DB::table('savings')->join('members', 'members.id', '=', 'savings.member_id')->select('savings.*', 'members.name')->get();
        $loans = DB::table('loans')->join('members', 'members.id', '=', 'loans.member_id')->select('loans.*', 'members.name')->get();
        $member = $id && $page === 'anggota' ? $allMembers->firstWhere('id', $id) : null;
        $receipt = $page === 'bukti' ? $payments->firstWhere('id', $id) : null;
        if (($page === 'anggota' && $id && ! $member) || ($page === 'bukti' && ! $receipt)) {
            abort(404);
        }
        $billItems = DB::table('bill_items')->get()->groupBy('bill_id');
        foreach ($bills as $bill) {
            $details = $billItems->get($bill->id, collect());
            $bill->remaining = $details->where('paid', false)->sum('amount');
            $bill->components = $details->pluck('category')->unique()->implode(', ');
        }
        $allocations = DB::table('payment_items')->join('bill_items', 'bill_items.id', '=', 'payment_items.bill_item_id')->join('bills', 'bills.id', '=', 'bill_items.bill_id')->select('payment_items.*', 'bill_items.category', 'bills.period')->get();
        $receiptItems = $receipt ? $allocations->where('payment_id', $receipt->id) : collect();
        $paymentPeriodTotal = $allocations->where('period', $period)->sum('amount');
        $paymentMemberId = old('member_id', $r->query('member'));
        if (! $paymentMemberId && $r->filled('bill')) {
            $paymentMemberId = DB::table('bills')->where('id', $r->query('bill'))->value('member_id');
        }
        $editingMember = null;
        if ($page === 'anggota' && Auth::user()->role === 'bendahara') {
            $r->validate(['edit' => 'nullable|integer|min:1']);
            $editId = session()->hasOldInput('member_form') ? old('id') : $r->query('edit');
            if ($editId && filter_var($editId, FILTER_VALIDATE_INT)) {
                $editingMember = DB::table('members')->where('id', (int) $editId)->first();
                abort_unless($editingMember, 404);
            }
        }

        return view('app', compact('receiptItems', 'paymentPeriodTotal', 'paymentMemberId', 'editingMember', 'page', 'titles', 'members', 'allMembers', 'bills', 'payments', 'cash', 'savings', 'loans', 'period', 'q', 'member', 'receipt'));
    }

    public function mutate(Request $r, string $action)
    {
        abort_unless(Auth::user()->role === 'bendahara', 403);
        if ($action === 'generate') {
            $v = $r->validate(['period' => 'required|date_format:Y-m', 'amount' => 'required|integer|min:1|max:100000000']);
            foreach (DB::table('members')->where('status', 'Aktif')->get() as $m) {
                DB::transaction(function () use ($m, $v) {
                    $inserted = DB::table('bills')->insertOrIgnore(['member_id' => $m->id, 'period' => $v['period'], 'amount' => $v['amount'], 'status' => 'Draft']);
                    if ($inserted) {
                        $billId = DB::table('bills')->where('member_id', $m->id)->where('period', $v['period'])->value('id');
                        DB::table('bill_items')->insert(['bill_id' => $billId, 'category' => 'Simpanan Wajib', 'amount' => $v['amount']]);
                    }
                });
            }
        } elseif ($action === 'finalize') {
            $r->validate(['period' => 'required|date_format:Y-m']);
            DB::table('bills')->where('period', $r->period)->where('status', 'Draft')->update(['status' => 'Final']);
        } elseif ($action === 'expense') {
            $v = $r->validate(['date' => 'required|date', 'category' => 'required|in:Operasional,Administrasi,Sosial', 'description' => 'required|max:255', 'amount' => 'required|integer|min:1|max:100000000']);
            $v['amount'] = -(int) $v['amount'];
            $v['user_id'] = Auth::id();
            DB::table('cash_entries')->insert($v);
        } else {
            abort(404);
        }
        $this->audit('Perubahan '.$action);

        return back()->with('success', 'Data berhasil disimpan.');
    }

    private function audit($action)
    {
        DB::table('audit_entries')->insert(['user_id' => Auth::id(), 'action' => $action, 'created_at' => now()]);
    }
}
