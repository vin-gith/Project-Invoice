<?php

namespace App\Support;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use Illuminate\Support\Facades\URL;

/**
 * Kirim invoice lewat WhatsApp: membuka wa.me dengan pesan yang sudah terisi (nomor pelanggan, total,
 * jatuh tempo, info transfer, dan link PDF). Tidak memakai API WhatsApp berbayar; pengguna tinggal menekan kirim
 * di aplikasi WhatsApp-nya sendiri.
 */
class WhatsApp
{
    /** Berapa hari link PDF di dalam pesan bisa dibuka pelanggan. */
    public const LINK_DAYS = 30;

    public static function invoiceLink(Invoice $invoice): string
    {
        return 'https://wa.me/' . self::number($invoice->customer_phone) . '?text=' . rawurlencode(self::message($invoice));
    }

    /** "+62 812-3456-7890" -> "6281234567890". Kosong kalau nomornya tidak dikenali (pengguna memilih kontak sendiri). */
    public static function number(?string $phone): string
    {
        $rest = Phone::rest($phone);

        return $rest !== '' ? '62' . $rest : '';
    }

    /**
     * Link PDF untuk pelanggan: tanpa login, tapi ditandatangani (signed) dan kedaluwarsa setelah LINK_DAYS hari.
     * Mengubah nomor invoice di alamat membuat tanda tangannya tidak cocok, jadi invoice lain tidak bisa diintip.
     */
    public static function pdfUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute('invoices.public', now()->addDays(self::LINK_DAYS), ['invoice' => $invoice->id]);
    }

    /** Isi pesan. Kalimatnya menyesuaikan status: tagihan baru, pengingat (terlambat), atau terima kasih (lunas). */
    public static function message(Invoice $invoice): string
    {
        $seller = $invoice->seller();
        $date = fn ($d) => $d->copy()->locale('id')->translatedFormat('d F Y');
        $total = Invoice::rupiah($invoice->total);

        $lines = ["Halo {$invoice->customer_name},", ''];

        if ($invoice->isPaid()) {
            $lines[] = "Terima kasih, pembayaran invoice {$invoice->number} dari {$seller->name} sudah kami terima.";
            $lines[] = "Total: {$total}" . ($invoice->paid_at ? ' (lunas ' . $date($invoice->paid_at) . ')' : ' (lunas)');
        } elseif ($invoice->statusKey() === 'overdue') {
            $late = (int) abs($invoice->due_date->diffInDays(today()));
            $lines[] = "Kami ingin mengingatkan invoice {$invoice->number} dari {$seller->name} yang sudah lewat jatuh tempo.";
            $lines[] = "Total tagihan: {$total}";
            $lines[] = 'Jatuh tempo: ' . $date($invoice->due_date) . " (lewat {$late} hari)";
        } else {
            $lines[] = "Berikut invoice {$invoice->number} dari {$seller->name}.";
            $lines[] = "Total tagihan: {$total}";
            $lines[] = 'Jatuh tempo: ' . $date($invoice->due_date);
        }

        $lines[] = '';
        $lines[] = 'Lihat atau unduh invoice (PDF):';
        $lines[] = self::pdfUrl($invoice);

        // Info transfer dari menu "Template invoice", hanya untuk tagihan yang belum lunas.
        $profile = BusinessProfile::stored($invoice->user_id);
        if (! $invoice->isPaid() && $profile && filled($profile->bank_account)) {
            $lines[] = '';
            $lines[] = 'Pembayaran dapat ditransfer ke:';
            $lines[] = trim($profile->bank_name . ' ' . $profile->bank_account);
            if (filled($profile->bank_holder)) {
                $lines[] = 'a.n. ' . $profile->bank_holder;
            }
        }

        $lines[] = '';
        $lines[] = 'Terima kasih.';
        $lines[] = $seller->name;

        return implode("\n", $lines);
    }
}
