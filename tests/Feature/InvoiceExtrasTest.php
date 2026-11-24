<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Kirim lewat WhatsApp + link PDF publik, duplikat invoice, dan halaman Laporan. */
class InvoiceExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(User $user, array $attrs = []): Invoice
    {
        static $seq = 0;
        $seq++;

        $invoice = Invoice::create(array_merge([
            'user_id' => $user->id,
            'number' => 'INV-01-01-2026-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'seller_name' => 'Toko Budi',
            'seller_address' => 'Jl. Merdeka No. 1',
            'seller_email' => 'budi@example.com',
            'seller_phone' => '+62 812-3456-7890',
            'customer_name' => 'PT Sumber Makmur',
            'customer_phone' => '+62 821-1234-5678',
            'issue_date' => today()->subDays(3),
            'due_date' => today()->addDays(11),
            'tax_percent' => 0,
            'status' => 'unpaid',
        ], $attrs));

        $invoice->items()->create(['position' => 0, 'description' => 'Kertas A4', 'qty' => 2, 'price' => 50000, 'line_total' => 100000]);
        $invoice->recalculate();

        return $invoice->refresh();
    }

    // ---------- WhatsApp + link publik ----------

    public function test_whatsapp_opens_wa_me_with_customer_number_and_signed_pdf_link(): void
    {
        $user = User::factory()->create();
        $invoice = $this->makeInvoice($user);

        $location = $this->actingAs($user)->get("/invoices/{$invoice->id}/whatsapp")->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://wa.me/6282112345678?text=', $location);
        $text = rawurldecode(substr($location, strpos($location, '?text=') + 6));
        $this->assertStringContainsString($invoice->number, $text);
        $this->assertStringContainsString('Rp 100.000', $text);
        $this->assertStringContainsString("/invoice/{$invoice->id}/lihat?expires=", $text);
        $this->assertStringContainsString('signature=', $text);
    }

    public function test_whatsapp_message_follows_status(): void
    {
        $user = User::factory()->create();

        $late = $this->makeInvoice($user, ['issue_date' => today()->subDays(20), 'due_date' => today()->subDays(5)]);
        $this->assertStringContainsString('lewat 5 hari', WhatsApp::message($late));

        $paid = $this->makeInvoice($user, ['status' => 'paid', 'paid_at' => now()]);
        $this->assertStringContainsString('sudah kami terima', WhatsApp::message($paid));
    }

    public function test_whatsapp_of_other_account_returns_404(): void
    {
        [$owner, $other] = User::factory()->count(2)->create();
        $invoice = $this->makeInvoice($owner);

        $this->actingAs($other)->get("/invoices/{$invoice->id}/whatsapp")->assertNotFound();
    }

    public function test_public_pdf_opens_without_login_only_with_valid_signature(): void
    {
        $owner = User::factory()->create();
        $invoice = $this->makeInvoice($owner);
        $other = $this->makeInvoice($owner, ['customer_name' => 'Pelanggan Lain']);

        $url = WhatsApp::pdfUrl($invoice);

        // Pelanggan (tidak login) membuka link asli.
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');

        // Tanpa tanda tangan, atau nomor invoice di alamat diganti: ditolak.
        $this->get("/invoice/{$invoice->id}/lihat")->assertForbidden();
        $this->get(str_replace("/invoice/{$invoice->id}/", "/invoice/{$other->id}/", $url))->assertForbidden();

        // Sudah kedaluwarsa: ditolak.
        $expired = URL::temporarySignedRoute('invoices.public', now()->subMinute(), ['invoice' => $invoice->id]);
        $this->get($expired)->assertForbidden();
    }

    public function test_public_pdf_and_message_use_the_owners_profile_without_login(): void
    {
        $owner = User::factory()->create();
        \App\Models\BusinessProfile::create([
            'name' => 'Toko Budi', 'address' => 'Jl. Merdeka', 'email' => 'budi@example.com', 'phone' => '+62 812-3456-7890',
            'pdf_template' => 'klasik', 'bank_name' => 'BCA', 'bank_account' => '123 456 7890', 'bank_holder' => 'Budi',
        ])->forceFill(['user_id' => $owner->id])->save();
        $invoice = $this->makeInvoice($owner);

        // Tidak ada yang login (seperti pelanggan yang membuka link).
        $this->assertGuest();
        $this->assertSame('klasik', \App\Models\BusinessProfile::design([], $invoice->user_id)['template']);
        $this->assertStringContainsString('BCA 123 456 7890', WhatsApp::message($invoice));
        $this->get(WhatsApp::pdfUrl($invoice))->assertOk();
    }

    // ---------- Duplikat ----------

    public function test_duplicate_copies_invoice_with_new_number_and_dates(): void
    {
        $user = User::factory()->create();
        $source = $this->makeInvoice($user, [
            'status' => 'paid',
            'paid_at' => now(),
            'issue_date' => today()->subDays(40),
            'due_date' => today()->subDays(26),   // tempo 14 hari
            'notes' => 'Bayar sebelum jatuh tempo.',
        ]);

        $response = $this->actingAs($user)->post("/invoices/{$source->id}/duplikat");

        $copy = Invoice::where('id', '!=', $source->id)->firstOrFail();
        $response->assertRedirect(route('invoices.edit', $copy));

        $this->assertNotSame($source->number, $copy->number);
        $this->assertSame('unpaid', $copy->status);
        $this->assertNull($copy->paid_at);
        $this->assertTrue($copy->issue_date->isSameDay(today()));
        $this->assertTrue($copy->due_date->isSameDay(today()->addDays(14)));
        $this->assertSame('PT Sumber Makmur', $copy->customer_name);
        $this->assertSame('Bayar sebelum jatuh tempo.', $copy->notes);
        $this->assertSame(100000, (int) $copy->total);
        $this->assertSame(['Kertas A4'], $copy->items->pluck('description')->all());

        // Item invoice asal tidak ikut berpindah.
        $this->assertCount(1, $source->fresh()->items);
    }

    public function test_duplicate_of_other_account_returns_404(): void
    {
        [$owner, $other] = User::factory()->count(2)->create();
        $invoice = $this->makeInvoice($owner);

        $this->actingAs($other)->post("/invoices/{$invoice->id}/duplikat")->assertNotFound();
        $this->assertDatabaseCount('invoices', 1);
    }

    // ---------- Laporan ----------

    public function test_report_shows_own_income_and_receivables_only(): void
    {
        [$me, $someone] = User::factory()->count(2)->create();

        $this->makeInvoice($me, ['status' => 'paid', 'paid_at' => now(), 'customer_name' => 'Pelanggan Lunas']);       // pemasukan bulan ini
        $this->makeInvoice($me, ['issue_date' => today()->subDays(20), 'due_date' => today()->subDays(2), 'customer_name' => 'Pelanggan Telat']);
        $this->makeInvoice($someone, ['status' => 'paid', 'paid_at' => now(), 'customer_name' => 'Rahasia Akun Lain']);

        $this->actingAs($me)->get('/laporan')
            ->assertOk()
            ->assertSee('Pemasukan per bulan')
            ->assertSee('Rp 100.000')
            ->assertSee('Pelanggan Lunas')
            ->assertSee('Pelanggan Telat')
            ->assertSee('Lewat 2 hari')
            ->assertDontSee('Rahasia Akun Lain');

        $this->actingAs($me)->get('/laporan?bulan=12')->assertOk()->assertSee('12 bulan');
    }

    public function test_report_without_paid_invoices_shows_empty_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/laporan')
            ->assertOk()
            ->assertSee('Belum ada invoice lunas di periode ini.')
            ->assertSee('Semua invoice sudah lunas.');
    }
}
