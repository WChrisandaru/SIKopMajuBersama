<?php
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
?>
<dialog id="member-drawer" class="member-drawer" aria-labelledby="member-drawer-title" data-auto-open="<?php echo e($reopen ? 'true' : 'false'); ?>">
    <form id="member-form" action="<?php echo e(route('members.save')); ?>" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="member_form" value="1">
        <?php if($editing): ?><input type="hidden" name="id" value="<?php echo e($editingMember->id); ?>"><?php endif; ?>
        <header class="drawer-header">
            <div><span class="eyebrow">DATA KEANGGOTAAN</span><h2 id="member-drawer-title"><?php echo e($editing ? 'Edit anggota' : 'Tambah anggota'); ?></h2></div>
            <button type="button" class="drawer-close" data-close-member aria-label="Tutup formulir anggota">×</button>
        </header>
        <div class="drawer-content">
            <?php if($errors->any()): ?><div class="alert error" role="alert" tabindex="-1" id="member-errors">Data belum disimpan. Periksa kolom yang ditandai di bawah.<?php $__errorArgs = ['id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div><?php endif; ?>
            <div class="member-number"><div><small>NOMOR ANGGOTA</small><strong><?php echo e($editingMember->number ?? 'Dibuat otomatis setelah disimpan'); ?></strong></div><span class="badge">Otomatis · tidak dapat diedit</span></div>
            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <fieldset class="member-section"><legend><?php echo e($loop->iteration); ?>. <?php echo e($group['title']); ?></legend><p class="muted"><?php echo e($group['description']); ?></p>
                    <div class="member-fields">
                    <?php $__currentLoopData = $group['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$field, $label, $type, $max, $help]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="member-field <?php echo e(in_array($field, ['name','address','account_holder','mother_name']) ? 'full' : ''); ?>">
                            <label for="member-<?php echo e($field); ?>"><?php echo e($label); ?><?php if($field==='name'): ?> <span aria-hidden="true">*</span><?php endif; ?></label>
                            <?php if($type==='textarea'): ?>
                                <textarea id="member-<?php echo e($field); ?>" name="<?php echo e($field); ?>" rows="3" maxlength="<?php echo e($max); ?>" aria-describedby="help-<?php echo e($field); ?> error-<?php echo e($field); ?>" <?php if($errors->has($field)): ?> aria-invalid="true" <?php endif; ?>><?php echo e(old($field, $editingMember->{$field} ?? '')); ?></textarea>
                            <?php else: ?>
                                <input id="member-<?php echo e($field); ?>" name="<?php echo e($field); ?>" type="<?php echo e($type); ?>" maxlength="<?php echo e($max); ?>" value="<?php echo e(old($field, $editingMember->{$field} ?? '')); ?>" <?php if($field==='name'): echo 'required'; endif; ?> <?php if(in_array($field,['nik','account_number'])): ?> inputmode="numeric" <?php endif; ?> autocomplete="off" aria-describedby="help-<?php echo e($field); ?> error-<?php echo e($field); ?>" <?php if($errors->has($field)): ?> aria-invalid="true" <?php endif; ?>>
                            <?php endif; ?>
                            <small id="help-<?php echo e($field); ?>"><?php echo e($help); ?></small>
                            <span class="field-error" id="error-<?php echo e($field); ?>"><?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><?php echo e($message); ?><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if($loop->first): ?>
                        <div class="member-field full"><label for="member-status">Status anggota *</label><select id="member-status" name="status" required aria-describedby="help-status error-status">
                            <?php $__currentLoopData = ['Aktif','Nonaktif']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(old('status',$editingMember->status??'Aktif')===$status): echo 'selected'; endif; ?>><?php echo e($status); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select><small id="help-status">Menonaktifkan anggota tidak menghapus histori transaksi.</small><span class="field-error" id="error-status"><?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><?php echo e($message); ?><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></span></div>
                    <?php endif; ?>
                    <?php if($loop->last): ?>
                        <div class="member-field full"><label for="member-emergency_relationship">Hubungan keluarga</label><select id="member-emergency_relationship" name="emergency_relationship" aria-describedby="error-emergency_relationship" <?php if($errors->has('emergency_relationship')): ?> aria-invalid="true" <?php endif; ?>><option value="">Pilih hubungan keluarga</option><?php $__currentLoopData = ['Suami/Istri','Orang Tua','Anak','Saudara Kandung','Lainnya']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relationship): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(old('emergency_relationship',$editingMember->emergency_relationship??'')===$relationship): echo 'selected'; endif; ?>><?php echo e($relationship); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><span class="field-error" id="error-emergency_relationship"><?php $__errorArgs = ['emergency_relationship'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><?php echo e($message); ?><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></span></div>
                    <?php endif; ?>
                    </div>
                </fieldset>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <footer class="drawer-footer"><small>Periksa kembali sebelum menyimpan.</small><div><button type="button" data-close-member>Batal</button><button type="submit" class="primary"><?php echo e($editing ? 'Simpan perubahan' : 'Simpan anggota'); ?></button></div></footer>
    </form>
</dialog>
<script src="/member-drawer.js" defer></script>
<?php /**PATH /Users/wchrisandaru/Documents/GitHub/SIKopMajuBersama/resources/views/members/drawer.blade.php ENDPATH**/ ?>