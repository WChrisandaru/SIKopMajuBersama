<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_assigns_number_and_preserves_leading_zeroes(): void
    {
        $user = User::factory()->create(['role' => 'bendahara']);
        $values = ['name' => 'Anggota Uji', 'status' => 'Aktif', 'nik' => '0123456789012345', 'mother_name' => 'Ibu Contoh', 'bank_name' => 'Bank Contoh', 'account_number' => '0012345678', 'account_holder' => 'Nama Rekening Uji', 'emergency_name' => 'Kontak Contoh', 'emergency_phone' => '081234567890', 'emergency_relationship' => 'Orang Tua', 'number' => 'MB-9999'];

        $this->actingAs($user)->post('/action/member', $values)->assertRedirect('/anggota/1');

        $this->assertDatabaseHas('members', [...array_diff_key($values, ['number' => true]), 'number' => 'MB-0001']);
        $this->assertDatabaseHas('audit_entries', ['user_id' => $user->id, 'action' => 'Tambah anggota MB-0001']);
    }

    public function test_edit_keeps_number_and_logs_changed_fields_without_sensitive_values(): void
    {
        $user = User::factory()->create(['role' => 'bendahara']);
        $id = DB::table('members')->insertGetId(['number' => 'MB-001', 'name' => 'Nama Lama', 'status' => 'Aktif', 'nik' => '0123456789012345']);

        $this->actingAs($user)->post('/action/member', ['id' => $id, 'number' => 'MB-9999', 'name' => 'Nama Baru', 'status' => 'Nonaktif', 'nik' => '0123456789012345'])->assertRedirect('/anggota/'.$id);

        $this->assertDatabaseHas('members', ['id' => $id, 'number' => 'MB-001', 'name' => 'Nama Baru', 'status' => 'Nonaktif']);
        $this->assertDatabaseHas('audit_entries', ['user_id' => $user->id, 'action' => 'Ubah anggota MB-001; kolom: name, status']);
    }

    public function test_numbers_continue_after_legacy_seeded_members_and_nonactivation(): void
    {
        $this->seed();
        DB::table('members')->where('id', 12)->update(['status' => 'Nonaktif']);

        $this->actingAs(User::where('role', 'bendahara')->first())->post('/action/member', ['name' => 'Anggota Berikutnya', 'status' => 'Aktif'])->assertRedirect('/anggota/13');

        $this->assertDatabaseHas('members', ['name' => 'Anggota Berikutnya', 'number' => 'MB-0013']);
        $this->assertDatabaseHas('members', ['id' => 12, 'number' => 'MB-012']);
    }

    public function test_number_is_not_reused_after_a_record_is_removed(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'bendahara']));
        $this->post('/action/member', ['name' => 'Pertama', 'status' => 'Aktif'])->assertRedirect();
        DB::table('members')->delete();

        $this->post('/action/member', ['name' => 'Kedua', 'status' => 'Aktif'])->assertRedirect();

        $this->assertDatabaseHas('members', ['name' => 'Kedua', 'number' => 'MB-0002']);
    }

    public function test_rejects_duplicate_nik_and_preserves_form_input(): void
    {
        $user = User::factory()->create(['role' => 'bendahara']);
        DB::table('members')->insert(['number' => 'MB-0001', 'name' => 'Lama', 'status' => 'Aktif', 'nik' => '0123456789012345']);

        $this->actingAs($user)->from('/anggota?create=1')->post('/action/member', ['member_form' => '1', 'name' => 'Baru', 'status' => 'Aktif', 'nik' => '0123456789012345'])
            ->assertRedirect('/anggota?create=1')->assertSessionHasErrors(['nik' => 'NIK sudah digunakan oleh anggota lain.'])->assertSessionHasInput('name', 'Baru');

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseCount('audit_entries', 0);
    }

    #[DataProvider('invalidProfiles')]
    public function test_rejects_invalid_profile_without_creating_member(array $extra, array $errors): void
    {
        $user = User::factory()->create(['role' => 'bendahara']);

        $this->actingAs($user)->post('/action/member', array_replace(['name' => 'Uji', 'status' => 'Aktif'], $extra))->assertSessionHasErrors($errors);

        $this->assertDatabaseCount('members', 0);
        $this->assertDatabaseHas('member_number_sequences', ['id' => 1, 'last_number' => 0]);
    }

    public static function invalidProfiles(): array
    {
        return [
            'missing name' => [['name' => ''], ['name' => 'nama lengkap wajib diisi.']],
            'short nik' => [['nik' => '123'], ['nik' => 'NIK harus terdiri dari 16 digit angka.']],
            'non numeric nik' => [['nik' => '123456789012345X'], ['nik']],
            'incomplete bank' => [['account_number' => '001234'], ['bank_name', 'account_holder']],
            'incomplete emergency' => [['emergency_name' => 'Darurat'], ['emergency_phone', 'emergency_relationship']],
            'invalid relationship' => [['emergency_name' => 'Darurat', 'emergency_phone' => '081234567890', 'emergency_relationship' => 'Penerima dana'], ['emergency_relationship']],
            'invalid account' => [['bank_name' => 'Contoh', 'account_holder' => 'Contoh', 'account_number' => '123ABC'], ['account_number']],
            'invalid phone' => [['contact' => 'telepon saya'], ['contact']],
            'invalid status' => [['status' => 'Dihapus'], ['status']],
            'invalid edit identifier' => [['id' => '1 OR 1=1'], ['id']],
            'nonexistent member' => [['id' => 500], ['id']],
        ];
    }

    public function test_ketua_cannot_save_or_open_sensitive_edit_form(): void
    {
        $user = User::factory()->create(['role' => 'ketua']);
        $id = DB::table('members')->insertGetId(['number' => 'MB-001', 'name' => 'Anggota', 'status' => 'Aktif', 'nik' => '0123456789012345', 'mother_name' => 'Ibu Rahasia', 'account_number' => '000123456']);

        $this->actingAs($user)->post('/action/member', ['id' => $id, 'name' => 'Diubah', 'status' => 'Aktif'])->assertForbidden();
        $this->get('/anggota?edit='.$id)->assertOk()->assertDontSee('member-drawer')->assertDontSee('0123456789012345')->assertDontSee('Ibu Rahasia');
        $this->assertDatabaseHas('members', ['id' => $id, 'name' => 'Anggota']);
    }

    public function test_guest_must_login_to_save_member(): void
    {
        $this->post('/action/member', ['name' => 'Tamu', 'status' => 'Aktif'])->assertRedirect('/login');
        $this->assertDatabaseCount('members', 0);
    }

    public function test_edit_drawer_escapes_values_and_preserves_old_input_after_validation(): void
    {
        $user = User::factory()->create(['role' => 'bendahara']);
        $id = DB::table('members')->insertGetId(['number' => 'MB-001', 'name' => '<script>alert(1)</script>', 'status' => 'Nonaktif']);

        $this->actingAs($user)->get('/anggota?edit='.$id)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->from('/anggota?edit='.$id)->post('/action/member', ['member_form' => 1, 'id' => $id, 'name' => 'Nama diperbaiki', 'status' => 'Nonaktif', 'nik' => '123'])->assertSessionHasErrors('nik');
        $this->get('/anggota?edit='.$id)->assertOk()->assertSee('value="Nama diperbaiki"', false)->assertSee('data-auto-open="true"', false);
        $this->assertDatabaseHas('members', ['id' => $id, 'name' => '<script>alert(1)</script>']);
    }
}
