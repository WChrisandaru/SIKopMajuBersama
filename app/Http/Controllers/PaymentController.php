<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function members(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'bendahara', 403);
        $v = $request->validate(['q' => 'required|string|min:2|max:100', 'page' => 'nullable|integer|min:1']);
        $query = trim($v['q']);
        $members = DB::table('members')->select('id', 'number', 'name', 'address', 'status')
            ->where(fn ($b) => $b->where('name', 'like', '%'.$query.'%')->orWhere('number', 'like', '%'.$query.'%'))
            ->orderBy('name')->orderBy('id')->simplePaginate(10);

        return response()->json($members);
    }

    public function items(Request $request, int $member): JsonResponse
    {
        abort_unless($request->user()->role === 'bendahara', 403);
        $record = DB::table('members')->select('id', 'number', 'name', 'address', 'status')->where('id', $member)->first();
        abort_unless($record, 404);
        $items = DB::table('bill_items')->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->where('bills.member_id', $member)->whereIn('bills.status', ['Final', 'Sebagian'])->where('bill_items.paid', false)
            ->select('bill_items.id', 'bill_items.category', 'bill_items.amount', 'bill_items.due_date', 'bills.period')
            ->orderBy('bills.period')->orderBy('bill_items.id')->get();

        return response()->json(['member' => $record, 'items' => $items]);
    }

    public function addComponent(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'bendahara', 403);
        $v = $request->validate([
            'bill_id' => 'required|integer|exists:bills,id',
            'category' => 'required|in:Simpanan Wajib,Angsuran Pokok Reguler,Angsuran Pokok Sebrakan,Bunga Reguler,Bunga Sebrakan,Denda,Arisan,Arisan Emas,Sosial',
            'amount' => 'required|integer|min:1|max:100000000',
            'due_date' => 'nullable|date_format:Y-m-d',
            'loan_id' => 'nullable|integer|exists:loans,id',
        ]);
        DB::transaction(function () use ($v, $request): void {
            $bill = DB::table('bills')->where('id', $v['bill_id'])->lockForUpdate()->first();
            if ($bill->status !== 'Draft') {
                throw ValidationException::withMessages(['bill_id' => 'Komponen hanya dapat ditambahkan pada tagihan draft.']);
            }
            if (in_array($v['category'], ['Angsuran Pokok Reguler', 'Angsuran Pokok Sebrakan'])) {
                $expected = $v['category'] === 'Angsuran Pokok Reguler' ? 'Reguler' : 'Sebrakan';
                $loan = DB::table('loans')->where('id', $v['loan_id'] ?? null)->where('member_id', $bill->member_id)->where('type', $expected)->first();
                if (! $loan || $loan->balance < (int) $v['amount']) {
                    throw ValidationException::withMessages(['loan_id' => 'Pilih pinjaman milik anggota ini, dengan jenis dan saldo yang sesuai.']);
                }
            } elseif (! empty($v['loan_id'])) {
                throw ValidationException::withMessages(['loan_id' => 'Referensi pinjaman hanya digunakan untuk angsuran pokok.']);
            }
            if (DB::table('bill_items')->where('bill_id', $bill->id)->where('category', $v['category'])->where('loan_id', $v['loan_id'] ?? null)->exists()) {
                throw ValidationException::withMessages(['category' => 'Komponen ini sudah ada pada tagihan. Periksa rincian sebelum menambahkan.']);
            }
            DB::table('bill_items')->insert($v);
            DB::table('bills')->where('id', $bill->id)->increment('amount', (int) $v['amount']);
            DB::table('audit_entries')->insert(['user_id' => $request->user()->id, 'action' => 'Tambah komponen '.$v['category'].' pada tagihan #'.$bill->id, 'created_at' => now()]);
        });

        return back()->with('success', 'Komponen draft berhasil ditambahkan. Tinjau rincian sebelum finalisasi.');
    }

    public function save(SavePaymentRequest $request): RedirectResponse
    {
        $v = $request->validated();
        $paymentId = DB::transaction(function () use ($v, $request): int {
            $items = DB::table('bill_items')->join('bills', 'bills.id', '=', 'bill_items.bill_id')
                ->whereIn('bill_items.id', $v['items'])->select('bill_items.*', 'bills.member_id', 'bills.status')->orderBy('bill_items.id')->lockForUpdate()->get();
            if ($items->count() !== count($v['items']) || $items->contains(fn ($i) => $i->member_id != (int) $v['member_id'] || $i->paid || ! in_array($i->status, ['Final', 'Sebagian']))) {
                throw ValidationException::withMessages(['items' => 'Tagihan berubah, sudah lunas, masih draft, atau bukan milik anggota ini. Muat ulang tagihan.']);
            }
            $total = (int) $items->sum('amount');
            if ($total !== (int) $v['amount']) {
                throw ValidationException::withMessages(['amount' => 'Total tidak sesuai dengan komponen terpilih. Periksa kembali tagihan.']);
            }
            foreach ($items as $item) {
                if (! in_array($item->category, ['Simpanan Wajib', 'Angsuran Pokok Reguler', 'Angsuran Pokok Sebrakan', 'Bunga Reguler', 'Bunga Sebrakan', 'Denda', 'Arisan', 'Arisan Emas', 'Sosial'])) {
                    throw ValidationException::withMessages(['items' => 'Kategori tagihan belum didukung. Periksa data tagihan.']);
                }
                if (DB::table('bill_items')->where('id', $item->id)->where('paid', false)->update(['paid' => true]) !== 1) {
                    throw ValidationException::withMessages(['items' => 'Komponen telah dibayar. Muat ulang tagihan.']);
                }
            }
            $paymentId = DB::table('payments')->insertGetId(['bill_id' => $items->first()->bill_id, 'user_id' => $request->user()->id, 'amount' => $total, 'method' => $v['method'], 'date' => $v['date'], 'reference' => 'MB-'.strtoupper(bin2hex(random_bytes(8))), 'recorded_at' => now()]);
            foreach ($items as $item) {
                DB::table('payment_items')->insert(['payment_id' => $paymentId, 'bill_item_id' => $item->id, 'amount' => $item->amount]);
                DB::table('cash_entries')->insert(['payment_id' => $paymentId, 'date' => $v['date'], 'category' => $item->category, 'description' => $item->category.' · tagihan #'.$item->bill_id, 'amount' => $item->amount, 'user_id' => $request->user()->id]);
                if ($item->category === 'Simpanan Wajib') {
                    DB::table('savings')->insert(['payment_id' => $paymentId, 'member_id' => $v['member_id'], 'type' => 'Wajib', 'date' => $v['date'], 'amount' => $item->amount]);
                }
                if (in_array($item->category, ['Angsuran Pokok Reguler', 'Angsuran Pokok Sebrakan'])) {
                    $loan = DB::table('loans')->where('id', $item->loan_id)->where('member_id', $v['member_id'])->lockForUpdate()->first();
                    $expected = $item->category === 'Angsuran Pokok Reguler' ? 'Reguler' : 'Sebrakan';
                    if (! $loan || $loan->type !== $expected || $loan->balance < $item->amount) {
                        throw ValidationException::withMessages(['items' => 'Referensi atau saldo pinjaman tidak sesuai. Pembayaran belum disimpan.']);
                    }
                    DB::table('loans')->where('id', $loan->id)->update(['balance' => $loan->balance - $item->amount, 'status' => $loan->balance === $item->amount ? 'Lunas' : $loan->status]);
                }
            }
            foreach ($items->pluck('bill_id')->unique() as $billId) {
                $remaining = DB::table('bill_items')->where('bill_id', $billId)->where('paid', false)->exists();
                DB::table('bills')->where('id', $billId)->update(['status' => $remaining ? 'Sebagian' : 'Lunas']);
            }
            DB::table('audit_entries')->insert(['user_id' => $request->user()->id, 'action' => 'Konfirmasi pembayaran #'.$paymentId, 'created_at' => now()]);

            return $paymentId;
        }, 3);

        return redirect('/bukti/'.$paymentId)->with('success', 'Pembayaran tersimpan. Tagihan, histori, dan Buku Kas telah diperbarui.');
    }
}
