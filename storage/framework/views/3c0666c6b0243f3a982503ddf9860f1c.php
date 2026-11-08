<section class="panel payment-panel" id="payment-panel" data-member="<?php echo e($paymentMemberId); ?>" data-old-items="<?php echo e(json_encode(old('items', []))); ?>" data-today="<?php echo e(now('Asia/Jakarta')->toDateString()); ?>">
    <div class="panel-heading"><div><span class="eyebrow">PENERIMAAN PEMBAYARAN</span><h3>1. Cari anggota</h3><p class="muted">Cari nama atau nomor anggota, lalu pilih kewajiban yang dibayar.</p></div></div>
    <div id="member-search-area">
        <label for="payment-member-search">Nama / nomor anggota</label>
        <input id="payment-member-search" type="search" autocomplete="off" placeholder="Ketik minimal 2 karakter, misalnya Anindya atau MB-001" aria-controls="payment-member-results">
        <p class="muted caption">Gunakan tombol panah atau Tab untuk memilih hasil pencarian.</p>
        <div id="payment-member-results" aria-label="Hasil pencarian anggota"></div>
        <div class="payment-search-pages" id="payment-search-pages" hidden><button type="button" id="search-prev">← Sebelumnya</button><span id="search-page"></span><button type="button" id="search-next">Berikutnya →</button></div>
    </div>
    <div id="selected-payment-member" class="selected-payment-member" hidden><div><span class="eyebrow">ANGGOTA TERPILIH</span><h3 id="selected-member-name"></h3><p id="selected-member-info"></p></div><button type="button" id="change-payment-member">Ganti anggota</button></div>
    <p id="payment-load-status" role="status" aria-live="polite"></p>
    <button type="button" id="reload-payment-items" hidden>Muat ulang tagihan</button>
    <form id="payment-form" action="<?php echo e(route('payments.save')); ?>" method="post" hidden>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="member_id" id="payment-member-id">
        <input type="hidden" name="amount" id="payment-amount" value="0">
        <input type="hidden" name="confirmed" id="payment-confirmed" value="0">
        <div class="panel-heading"><div><h3>2. Pilih komponen tagihan</h3><p class="muted">Setiap pilihan melunasi seluruh sisa komponen tersebut.</p></div><button type="button" id="clear-payment-items">Kosongkan pilihan</button></div>
        <div class="note">Hanya tagihan final yang belum lunas ditampilkan. Nominal berasal dari tagihan yang tercatat. Aturan Arisan dan Arisan Emas masih mengikuti hasil validasi mitra.</div>
        <div id="payment-bill-groups"></div>
        <div class="payment-total" aria-live="polite"><span><b id="payment-count">0 komponen dipilih</b><small>Total pembayaran</small></span><strong id="payment-total">Rp 0</strong></div>
        <h3>3. Periksa pembayaran</h3>
        <div class="form-grid">
            <label>Metode pembayaran<select name="method" id="payment-method"><option <?php if(old('method')==='Tunai'): echo 'selected'; endif; ?>>Tunai</option><option <?php if(old('method')==='Transfer'): echo 'selected'; endif; ?>>Transfer</option></select></label>
            <label>Tanggal pembayaran<input type="date" name="date" id="payment-date" value="<?php echo e(old('date',now('Asia/Jakarta')->toDateString())); ?>" max="<?php echo e(now('Asia/Jakarta')->toDateString()); ?>" required><small>Terisi hari ini. Ubah jika mencatat pembayaran sebelumnya.</small></label>
            <label class="wide check"><input type="checkbox" name="verified" id="payment-verified" value="1" required><span id="payment-verified-label">Saya sudah memeriksa penerimaan uang tunai.</span></label>
        </div>
        <div class="payment-bottom"><p class="muted">Waktu pencatatan disimpan otomatis, terpisah dari tanggal pembayaran dan jatuh tempo.</p><button type="button" class="primary" id="review-payment" disabled>Tinjau pembayaran →</button></div>
    </form>
    <noscript><div class="alert error">Aktifkan JavaScript untuk mencari anggota dan memilih komponen tagihan.</div></noscript>
</section>
<dialog id="payment-review" class="payment-review" aria-labelledby="payment-review-title"><div class="panel-heading"><h2 id="payment-review-title">Konfirmasi pembayaran</h2><button type="button" id="close-payment-review" aria-label="Tutup ringkasan">×</button></div><p id="review-member"></p><p id="review-method-date" class="muted"></p><div class="table-wrap"><table><thead><tr><th>Periode</th><th>Komponen</th><th>Nominal</th></tr></thead><tbody id="review-items"></tbody></table></div><div class="payment-total"><span>Total pembayaran</span><strong id="review-total"></strong></div><p>Pastikan anggota, komponen, dan dana yang diterima sudah sesuai.</p><div class="actions"><button type="button" id="back-payment-review">Kembali</button><button type="button" class="primary" id="submit-payment">Konfirmasi & simpan</button></div></dialog>
<script src="/payments.js" defer></script>
<?php /**PATH /Users/wchrisandaru/Documents/GitHub/SIKopMajuBersama/resources/views/payments/form.blade.php ENDPATH**/ ?>