<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /** Data contoh buat demo. Jalankan: php artisan db:seed --class=InvoiceSeeder */
    public function run(): void
    {
        // [pelanggan, email, alamat, hari terbit (relatif), hari jatuh tempo (relatif), pajak %, status, item]
        $samples = [
            ['Toko Maju Jaya', 'maju@example.com', 'Jl. Merdeka No. 12, Serang', -20, -6, 11, 'paid', [
                ['Kertas A4 80gsm (rim)', 10, 52000],
                ['Tinta printer hitam', 4, 85000],
            ]],
            ['CV Sumber Rejeki', 'sumber@example.com', 'Jl. Raya Cilegon Km 4', -12, -2, 11, 'unpaid', [
                ['Jasa instalasi jaringan kantor', 1, 2500000],
                ['Kabel LAN Cat6 (meter)', 80, 6500],
            ]],
            ['Bima Computer', 'bima@example.com', 'Ruko Taktakan Blok C3', -5, 9, 0, 'unpaid', [
                ['SSD 512GB', 3, 640000],
                ['RAM DDR4 8GB', 3, 410000],
                ['Jasa rakit PC', 3, 150000],
            ]],
            ['Warung Digital Nusantara', 'warung@example.com', 'Jl. Veteran No. 7, Serang', -2, 12, 11, 'paid', [
                ['Pembuatan website toko online', 1, 3500000],
                ['Domain dan hosting 1 tahun', 1, 900000],
            ]],
        ];

        // Data contoh dimasukkan ke akun yang paling pertama dibuat (tiap akun punya daftar invoice sendiri).
        $userId = DB::table('users')->min('id');
        if (! $userId) {
            $this->command?->warn('Belum ada akun. Daftar dulu lewat website, lalu jalankan seeder ini lagi.');

            return;
        }

        foreach ($samples as [$name, $email, $address, $issue, $due, $tax, $status, $items]) {
            DB::transaction(function () use ($userId, $name, $email, $address, $issue, $due, $tax, $status, $items) {
                $invoice = Invoice::create([
                    'user_id' => $userId,
                    'number' => Invoice::nextNumber($userId),
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'customer_address' => $address,
                    'issue_date' => now()->addDays($issue),
                    'due_date' => now()->addDays($due),
                    'tax_percent' => $tax,
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? now() : null,
                ]);

                foreach ($items as $position => [$description, $qty, $price]) {
                    $invoice->items()->create([
                        'position' => $position,
                        'description' => $description,
                        'qty' => $qty,
                        'price' => $price,
                        'line_total' => $qty * $price,
                    ]);
                }

                $invoice->recalculate();
            });
        }
    }
}
