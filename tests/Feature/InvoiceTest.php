<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    /** Isi form invoice yang valid. Subtotal 200.000, pajak 11% = 22.000, total 222.000. */
    private function payload(array $override = []): array
    {
        return array_merge([
            'seller_name' => 'Toko Budi',
            'seller_address' => 'Jl. Merdeka No. 1, Serang',
            'seller_email' => 'budi@example.com',
            'seller_phone' => '81234567890',
            'customer_name' => 'PT Sumber Makmur',
            'customer_phone' => '82112345678',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'tax_percent' => 11,
            'items' => [
                ['description' => 'Kertas A4', 'qty' => 2, 'price' => 50000],
                ['description' => 'Jasa instalasi', 'qty' => 1, 'price' => 100000],
            ],
        ], $override);
    }

    public function test_store_calculates_totals_on_server(): void
    {
        $user = User::factory()->create();

        // Angka total dari browser harus diabaikan.
        $this->actingAs($user)
            ->post('/invoices', $this->payload(['subtotal' => 1, 'total' => 1]))
            ->assertRedirect();

        $invoice = Invoice::first();
        $this->assertSame(200000, (int) $invoice->subtotal);
        $this->assertSame(22000, (int) $invoice->tax_amount);
        $this->assertSame(222000, (int) $invoice->total);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame('+62 812-3456-7890', $invoice->seller_phone);
        $this->assertCount(2, $invoice->items);
    }

    public function test_numbers_continue_per_account(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        $prefix = 'INV-' . now()->format('d-m-Y') . '-';

        $this->actingAs($a)->post('/invoices', $this->payload());
        $this->actingAs($a)->post('/invoices', $this->payload());
        $this->actingAs($b)->post('/invoices', $this->payload());

        $this->assertSame(
            [$prefix . '0001', $prefix . '0002'],
            Invoice::where('user_id', $a->id)->orderBy('id')->pluck('number')->all(),
        );
        // Akun lain mulai dari 0001 sendiri.
        $this->assertSame($prefix . '0001', Invoice::where('user_id', $b->id)->value('number'));
    }

    public function test_other_accounts_invoice_returns_404(): void
    {
        [$owner, $other] = User::factory()->count(2)->create();
        $this->actingAs($owner)->post('/invoices', $this->payload());
        $invoice = Invoice::first();

        $this->actingAs($other)->get("/invoices/{$invoice->id}")->assertNotFound();
        $this->actingAs($other)->get("/invoices/{$invoice->id}/pdf")->assertNotFound();
        $this->actingAs($other)->patch("/invoices/{$invoice->id}/status")->assertNotFound();
        $this->actingAs($other)->delete("/invoices/{$invoice->id}")->assertNotFound();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'unpaid']);
    }

    public function test_toggle_status_marks_paid_and_back(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/invoices', $this->payload());
        $invoice = Invoice::first();

        $this->actingAs($user)->patch("/invoices/{$invoice->id}/status");
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $this->actingAs($user)->patch("/invoices/{$invoice->id}/status");
        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_due_date_before_issue_date_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/invoices', $this->payload(['due_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('due_date');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_index_only_lists_own_invoices(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        $this->actingAs($a)->post('/invoices', $this->payload(['customer_name' => 'Pelanggan Milik A']));
        $this->actingAs($b)->post('/invoices', $this->payload(['customer_name' => 'Pelanggan Milik B']));

        $this->actingAs($a)->get('/invoices')
            ->assertOk()
            ->assertSee('Pelanggan Milik A')
            ->assertDontSee('Pelanggan Milik B');
    }
}
