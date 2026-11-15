<?php

namespace App\Support;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Satu-satunya tempat PDF invoice dirakit, dipakai oleh tombol Unduh/Cetak di invoice
 * dan oleh pratinjau di menu "Template invoice". Dengan begitu tampilannya pasti sama.
 */
class InvoicePdf
{
    /**
     * PDF untuk satu invoice. $override berisi pengaturan tampilan sementara (template, font, warna, dst.)
     * yang BELUM disimpan, dipakai untuk mencoba template; kosong = pakai pengaturan yang tersimpan.
     */
    public static function make(Invoice $invoice, array $override = [])
    {
        $invoice->loadMissing('items');
        $design = BusinessProfile::design($override);

        // Logo di kop dan watermark memakai logo milik invoice ini, bukan logo "Data usaha" saat ini.
        $logoPath = $invoice->sellerLogoPath();
        $design['logo'] = LogoImage::dataUri($logoPath);
        $design['watermark'] = LogoImage::watermark($logoPath);

        return Pdf::loadView('invoices.pdf', compact('invoice', 'design'))->setPaper('a4');
    }

    /** Invoice karangan (tidak disimpan ke database) untuk contoh tampilan saat akun belum punya invoice. */
    public static function sample(): Invoice
    {
        $items = collect([
            ['Kertas A4 80gsm (rim)', 10, 52000],
            ['Tinta printer hitam', 4, 85000],
            ['Jasa instalasi jaringan kantor', 1, 2500000],
        ])->map(fn ($row, $i) => new InvoiceItem([
            'position' => $i, 'description' => $row[0], 'qty' => $row[1], 'price' => $row[2], 'line_total' => $row[1] * $row[2],
        ]));

        $subtotal = (int) $items->sum('line_total');
        $tax = (int) round($subtotal * 11 / 100);

        $invoice = new Invoice([
            'number' => 'INV-' . now()->format('d-m-Y') . '-0001',
            'customer_name' => 'PT Sumber Makmur',
            'customer_address' => 'Jl. Veteran No. 5, Cilegon, Banten',
            'customer_email' => 'pembelian@sumbermakmur.co.id',
            'customer_phone' => '+62 812-3456-7890',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'tax_percent' => 11,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
            'status' => 'unpaid',
            'notes' => 'Ini hanya contoh tampilan. Data di atas bukan invoice sungguhan.',
        ]);
        $invoice->setRelation('items', $items);

        return $invoice;
    }
}
