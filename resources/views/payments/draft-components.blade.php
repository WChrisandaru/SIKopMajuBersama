@if($operator && $bills->where('status','Draft')->isNotEmpty())
<section class="panel"><details><summary>＋ Tambahkan komponen ke tagihan draft</summary><p class="muted">Nominal dimasukkan sesuai kewajiban anggota yang sudah disepakati. Penambahan ini tidak menghitung bunga, denda, atau aturan Arisan secara otomatis.</p>
<form method="post" action="{{ route('bills.components.store') }}" class="form-grid">@csrf
<label>Tagihan draft<select name="bill_id" required><option value="">Pilih anggota dan periode</option>@foreach($bills->where('status','Draft') as $draft)<option value="{{ $draft->id }}" @selected(old('bill_id')==$draft->id)>{{ $draft->number }} · {{ $draft->name }} · {{ $draft->period }}</option>@endforeach</select></label>
<label>Komponen<select name="category" required>@foreach(['Simpanan Wajib','Angsuran Pokok Reguler','Angsuran Pokok Sebrakan','Bunga Reguler','Bunga Sebrakan','Denda','Arisan','Arisan Emas','Sosial'] as $category)<option @selected(old('category')===$category)>{{ $category }}</option>@endforeach</select></label>
<label>Nominal (Rp)<input type="number" min="1" max="100000000" name="amount" value="{{ old('amount') }}" required></label>
<label>Jatuh tempo (opsional)<input type="date" name="due_date" value="{{ old('due_date') }}"></label>
<label class="wide">Pinjaman terkait (khusus angsuran pokok)<select name="loan_id"><option value="">Tidak terkait pinjaman</option>@foreach($loans->where('balance','>',0) as $loan)<option value="{{ $loan->id }}" @selected(old('loan_id')==$loan->id)>#{{ $loan->id }} · {{ $loan->name }} · {{ $loan->type }} · Sisa {{ $money($loan->balance) }}</option>@endforeach</select></label>
<div><button type="submit" class="primary">Tambah komponen draft</button></div></form></details></section>
@endif
