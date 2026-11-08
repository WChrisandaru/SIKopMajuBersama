@php
    $editing = (bool) $editingMember;
    $reopen = request()->filled('edit') || request()->boolean('create') || session()->hasOldInput('member_form');
    $groups = [
        ['title' => 'Identitas anggota', 'description' => 'Gunakan identitas resmi anggota. Kolom bertanda * wajib diisi.', 'fields' => [
            ['name', 'Nama lengkap sesuai KTP', 'text', 150, 'Tulis nama lengkap sesuai KTP, termasuk ejaan dan gelar jika tercantum.'],
            ['nik', 'NIK / Nomor KTP', 'text', 16, 'Opsional · 16 digit angka. Format yang benar bukan verifikasi keaslian identitas.'],
            ['mother_name', 'Nama ibu kandung', 'text', 150, 'Opsional · lengkapi jika diperlukan dalam administrasi koperasi.'],
        ]],
        ['title' => 'Kontak dan alamat', 'description' => 'Nomor yang dapat dihubungi untuk administrasi anggota.', 'fields' => [
            ['contact', 'Kontak utama / WhatsApp', 'tel', 30, 'Contoh: 081234567890 atau +6281234567890.'],
            ['address', 'Alamat', 'textarea', 1000, 'Cantumkan blok atau nomor rumah agar mudah dikenali.'],
        ]],
        ['title' => 'Rekening anggota', 'description' => 'Opsional. Jika diisi, lengkapi ketiga kolom rekening.', 'fields' => [
            ['bank_name', 'Nama bank', 'text', 150, 'Contoh: BCA, BRI, BNI, atau Mandiri.'],
            ['account_number', 'Nomor rekening', 'text', 34, 'Salin sesuai buku rekening; angka nol di depan tetap disimpan.'],
            ['account_holder', 'Nama pemilik rekening', 'text', 150, 'Gunakan penulisan sesuai buku rekening, meskipun berbeda dari nama di KTP.'],
        ]],
        ['title' => 'Kontak darurat', 'description' => 'Opsional. Kontak untuk dihubungi, bukan penetapan ahli waris atau penerima dana.', 'fields' => [
            ['emergency_name', 'Nama kontak darurat', 'text', 150, ''],
            ['emergency_phone', 'Nomor kontak darurat', 'tel', 30, 'Nomor telepon yang dapat dihubungi.'],
        ]],
    ];
@endphp
<dialog id="member-drawer" class="member-drawer" aria-labelledby="member-drawer-title" data-auto-open="{{ $reopen ? 'true' : 'false' }}">
    <form id="member-form" action="{{ route('members.save') }}" method="post">
        @csrf
        <input type="hidden" name="member_form" value="1">
        @if($editing)<input type="hidden" name="id" value="{{ $editingMember->id }}">@endif
        <header class="drawer-header">
            <div><span class="eyebrow">DATA KEANGGOTAAN</span><h2 id="member-drawer-title">{{ $editing ? 'Edit anggota' : 'Tambah anggota' }}</h2></div>
            <button type="button" class="drawer-close" data-close-member aria-label="Tutup formulir anggota">×</button>
        </header>
        <div class="drawer-content">
            @if($errors->any())<div class="alert error" role="alert" tabindex="-1" id="member-errors">Data belum disimpan. Periksa kolom yang ditandai di bawah.@error('id')<p>{{ $message }}</p>@enderror</div>@endif
            <div class="member-number"><div><small>NOMOR ANGGOTA</small><strong>{{ $editingMember->number ?? 'Dibuat otomatis setelah disimpan' }}</strong></div><span class="badge">Otomatis · tidak dapat diedit</span></div>
            @foreach($groups as $group)
                <fieldset class="member-section"><legend>{{ $loop->iteration }}. {{ $group['title'] }}</legend><p class="muted">{{ $group['description'] }}</p>
                    <div class="member-fields">
                    @foreach($group['fields'] as [$field, $label, $type, $max, $help])
                        <div class="member-field {{ in_array($field, ['name','address','account_holder','mother_name']) ? 'full' : '' }}">
                            <label for="member-{{ $field }}">{{ $label }}@if($field==='name') <span aria-hidden="true">*</span>@endif</label>
                            @if($type==='textarea')
                                <textarea id="member-{{ $field }}" name="{{ $field }}" rows="3" maxlength="{{ $max }}" aria-describedby="help-{{ $field }} error-{{ $field }}" @if($errors->has($field)) aria-invalid="true" @endif>{{ old($field, $editingMember->{$field} ?? '') }}</textarea>
                            @else
                                <input id="member-{{ $field }}" name="{{ $field }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ old($field, $editingMember->{$field} ?? '') }}" @required($field==='name') @if(in_array($field,['nik','account_number'])) inputmode="numeric" @endif autocomplete="off" aria-describedby="help-{{ $field }} error-{{ $field }}" @if($errors->has($field)) aria-invalid="true" @endif>
                            @endif
                            <small id="help-{{ $field }}">{{ $help }}</small>
                            <span class="field-error" id="error-{{ $field }}">@error($field){{ $message }}@enderror</span>
                        </div>
                    @endforeach
                    @if($loop->first)
                        <div class="member-field full"><label for="member-status">Status anggota *</label><select id="member-status" name="status" required aria-describedby="help-status error-status">
                            @foreach(['Aktif','Nonaktif'] as $status)<option @selected(old('status',$editingMember->status??'Aktif')===$status)>{{ $status }}</option>@endforeach
                        </select><small id="help-status">Menonaktifkan anggota tidak menghapus histori transaksi.</small><span class="field-error" id="error-status">@error('status'){{ $message }}@enderror</span></div>
                    @endif
                    @if($loop->last)
                        <div class="member-field full"><label for="member-emergency_relationship">Hubungan keluarga</label><select id="member-emergency_relationship" name="emergency_relationship" aria-describedby="error-emergency_relationship" @if($errors->has('emergency_relationship')) aria-invalid="true" @endif><option value="">Pilih hubungan keluarga</option>@foreach(['Suami/Istri','Orang Tua','Anak','Saudara Kandung','Lainnya'] as $relationship)<option @selected(old('emergency_relationship',$editingMember->emergency_relationship??'')===$relationship)>{{ $relationship }}</option>@endforeach</select><span class="field-error" id="error-emergency_relationship">@error('emergency_relationship'){{ $message }}@enderror</span></div>
                    @endif
                    </div>
                </fieldset>
            @endforeach
        </div>
        <footer class="drawer-footer"><small>Periksa kembali sebelum menyimpan.</small><div><button type="button" data-close-member>Batal</button><button type="submit" class="primary">{{ $editing ? 'Simpan perubahan' : 'Simpan anggota' }}</button></div></footer>
    </form>
</dialog>
<script src="/member-drawer.js" defer></script>
