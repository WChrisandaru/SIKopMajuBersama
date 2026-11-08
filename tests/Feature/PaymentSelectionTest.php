<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): array
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 11, 8)->setTime(9, 0));
        $this->seed();
        $this->actingAs(User::where('role', 'bendahara')->first());
        $bill = DB::table('bills')->where('member_id', 1)->first();
        $loan = DB::table('loans')->where('member_id', 1)->first();
        $principal = DB::table('bill_items')->insertGetId(['bill_id' => $bill->id, 'category' => 'Angsuran Pokok Reguler', 'amount' => 500000, 'loan_id' => $loan->id, 'due_date' => '2026-11-01']);
        $interest = DB::table('bill_items')->insertGetId(['bill_id' => $bill->id, 'category' => 'Bunga Reguler', 'amount' => 50000]);
        $arisan = DB::table('bill_items')->insertGetId(['bill_id' => $bill->id, 'category' => 'Arisan', 'amount' => 100000]);
        DB::table('bills')->where('id', $bill->id)->update(['amount' => 750000]);

        return ['member_id' => 1, 'items' => [1, $principal, $interest], 'amount' => 650000, 'method' => 'Tunai', 'date' => '2026-11-08', 'verified' => 1, 'confirmed' => 1];
    }

    public function test_search_returns_bounded_results_without_sensitive_member_fields(): void
    {
        $this->prepare();
        DB::table('members')->where('id', 1)->update(['nik' => '0123456789012345']);
        $response = $this->getJson('/payments/members?q=MB');
        $response->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'Anindya Putri')->assertDontSee('0123456789012345');
        $this->getJson('/payments/members?q=MB&page=2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/payments/members?q=tidakada')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_member_items_include_due_date_and_exclude_draft_or_paid_items(): void
    {
        $this->prepare();
        DB::table('bill_items')->where('id', 1)->update(['paid' => true]);
        $this->getJson('/payments/members/1/items')->assertOk()->assertJsonCount(3, 'items')->assertJsonPath('items.0.due_date', '2026-11-01');
        DB::table('bills')->where('id', 1)->update(['status' => 'Draft']);
        $this->getJson('/payments/members/1/items')->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_selection_posts_one_receipt_with_correct_ledger_allocation(): void
    {
        $data = $this->prepare();
        $this->post('/action/pay', $data)->assertRedirect('/bukti/1');
        $this->assertDatabaseHas('payments', ['id' => 1, 'amount' => 650000, 'date' => '2026-11-08']);
        $this->assertDatabaseCount('payment_items', 3);
        $this->assertDatabaseHas('bills', ['id' => 1, 'status' => 'Sebagian']);
        $this->assertDatabaseHas('loans', ['member_id' => 1, 'balance' => 2500000]);
        $this->assertSame(100000, (int) DB::table('savings')->where('payment_id', 1)->sum('amount'));
        $this->assertSame(650000, (int) DB::table('cash_entries')->where('payment_id', 1)->sum('amount'));
        $this->get('/bukti/1')->assertOk()->assertSee('Angsuran Pokok Reguler')->assertSee('Bunga Reguler')->assertSee('650.000');
        $this->get('/dashboard')->assertOk()->assertSee('1.200.000');
        $this->postJson('/action/pay', $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_remaining_component_can_be_paid_later_without_changing_savings(): void
    {
        $data = $this->prepare();
        $this->post('/action/pay', $data)->assertRedirect();
        $arisan = DB::table('bill_items')->where('category', 'Arisan')->value('id');
        $this->post('/action/pay', array_replace($data, ['items' => [$arisan], 'amount' => 100000]))->assertRedirect('/bukti/2');
        $this->assertDatabaseHas('bills', ['id' => 1, 'status' => 'Lunas']);
        $this->assertDatabaseMissing('savings', ['payment_id' => 2]);
        $this->assertDatabaseHas('cash_entries', ['payment_id' => 2, 'category' => 'Arisan', 'amount' => 100000]);
    }

    public function test_multi_period_payment_is_reported_in_each_bill_period(): void
    {
        $data = $this->prepare();
        $bill = DB::table('bills')->insertGetId(['member_id' => 1, 'period' => '2026-10', 'amount' => 25000, 'status' => 'Final']);
        $item = DB::table('bill_items')->insertGetId(['bill_id' => $bill, 'category' => 'Arisan Emas', 'amount' => 25000]);
        $this->post('/action/pay', array_replace($data, ['items' => [1, $item], 'amount' => 125000]))->assertRedirect('/bukti/1');
        $this->get('/bukti/1')->assertOk()->assertSee('2026-10')->assertSee('2026-11')->assertSee('Arisan Emas');
        $this->get('/laporan?period=2026-10')->assertOk()->assertSee('Pembayaran periode: Rp 25.000');
    }

    #[DataProvider('invalidSelections')]
    public function test_invalid_selections_leave_no_partial_writes(array $replace, string $error): void
    {
        $data = $this->prepare();
        $this->postJson('/action/pay', array_replace($data, $replace))->assertUnprocessable()->assertJsonValidationErrors($error);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('payment_items', 0);
        $this->assertDatabaseCount('cash_entries', 1);
        $this->assertDatabaseHas('bill_items', ['id' => 1, 'paid' => false]);
        $this->assertDatabaseHas('loans', ['member_id' => 1, 'balance' => 3000000]);
    }

    public static function invalidSelections(): array
    {
        return [
            'no selection' => [['items' => []], 'items'],
            'duplicate selection' => [['items' => [1, 1]], 'items.0'],
            'wrong member' => [['member_id' => 2], 'items'],
            'forged total' => [['amount' => 1], 'amount'],
            'unverified transfer' => [['method' => 'Transfer', 'verified' => 0], 'verified'],
            'missing confirmation' => [['confirmed' => 0], 'confirmed'],
            'future date' => [['date' => '2026-11-09'], 'date'],
            'invalid method' => [['method' => 'QR'], 'method'],
        ];
    }

    public function test_bad_loan_reference_rolls_back_payment_and_allocation(): void
    {
        $data = $this->prepare();
        DB::table('bill_items')->where('category', 'Angsuran Pokok Reguler')->update(['loan_id' => null]);
        $this->postJson('/action/pay', $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('payment_items', 0);
        $this->assertDatabaseHas('bill_items', ['id' => 1, 'paid' => false]);
        $this->assertDatabaseCount('cash_entries', 1);
    }

    public function test_today_uses_jakarta_and_backdating_records_actual_entry_time(): void
    {
        $data = $this->prepare();
        $this->get('/pembayaran')->assertOk()->assertSee('value="2026-11-08"', false);
        $this->post('/action/pay', array_replace($data, ['date' => '2026-11-05']))->assertRedirect();
        $this->assertDatabaseHas('payments', ['date' => '2026-11-05', 'recorded_at' => now()->toDateTimeString()]);
    }

    public function test_permissions_protect_search_items_and_save(): void
    {
        $data = $this->prepare();
        $this->actingAs(User::where('role', 'ketua')->first());
        $this->getJson('/payments/members?q=MB')->assertForbidden();
        $this->getJson('/payments/members/1/items')->assertForbidden();
        $this->postJson('/action/pay', $data)->assertForbidden();
        $this->get('/pembayaran')->assertOk()->assertDontSee('payment-form');
    }

    public function test_add_component_to_draft_updates_total_and_prevents_duplicates(): void
    {
        $this->prepare();
        DB::table('bills')->where('id', 2)->update(['status' => 'Draft']);
        $data = ['bill_id' => 2, 'category' => 'Arisan Emas', 'amount' => 75000, 'due_date' => '2026-11-10'];
        $this->post('/action/bill-component', $data)->assertRedirect();
        $this->assertDatabaseHas('bill_items', $data);
        $this->assertDatabaseHas('bills', ['id' => 2, 'amount' => 175000]);
        $this->postJson('/action/bill-component', $data)->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->assertDatabaseHas('bills', ['id' => 2, 'amount' => 175000]);
    }

    public function test_finalized_bill_components_cannot_be_changed(): void
    {
        $this->prepare();
        $this->postJson('/action/bill-component', ['bill_id' => 2, 'category' => 'Arisan Emas', 'amount' => 75000])->assertUnprocessable()->assertJsonValidationErrors('bill_id');
        $this->assertDatabaseHas('bills', ['id' => 2, 'amount' => 100000]);
    }

    public function test_principal_component_requires_correct_member_loan(): void
    {
        $this->prepare();
        DB::table('bills')->where('id', 2)->update(['status' => 'Draft']);
        $this->postJson('/action/bill-component', ['bill_id' => 2, 'category' => 'Angsuran Pokok Reguler', 'amount' => 75000, 'loan_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('loan_id');
        $this->assertDatabaseHas('bills', ['id' => 2, 'amount' => 100000]);
    }

    public function test_ketua_cannot_add_bill_components(): void
    {
        $this->prepare();
        $this->actingAs(User::where('role', 'ketua')->first());
        $this->postJson('/action/bill-component', ['bill_id' => 2, 'category' => 'Arisan', 'amount' => 75000])->assertForbidden();
    }
}
