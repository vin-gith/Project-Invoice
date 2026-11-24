<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 dengan kanal transparan (RGBA). dompdf butuh GD untuk PNG seperti ini. */
    private const PNG_RGBA = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function invoiceWithPngLogo(User $user): Invoice
    {
        $logo = UploadedFile::fake()->createWithContent('logo.png', base64_decode(self::PNG_RGBA));

        $this->actingAs($user)->post('/invoices', [
            'seller_name' => 'Toko Budi',
            'seller_address' => 'Jl. Merdeka No. 1, Serang',
            'seller_email' => 'budi@example.com',
            'seller_phone' => '81234567890',
            'logo' => $logo,
            'customer_name' => 'PT Sumber Makmur',
            'customer_phone' => '82112345678',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'tax_percent' => 11,
            'items' => [['description' => 'Kertas A4', 'qty' => 2, 'price' => 50000]],
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertNotNull($invoice->seller_logo_path);

        return $invoice;
    }

    public function test_download_print_and_preview_work_with_transparent_png_logo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $invoice = $this->invoiceWithPngLogo($user);

        $this->actingAs($user)->get("/invoices/{$invoice->id}/pdf")
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)->get("/invoices/{$invoice->id}/cetak")
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)->get("/template-invoice/pratinjau?invoice={$invoice->id}")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
