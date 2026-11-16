<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Merapikan nomor invoice yang SUDAH ada supaya berurutan per akun, sesuai urutan pembuatan
 * (invoice yang dibuat paling awal = 0001, berikutnya 0002, dan seterusnya).
 * Bagian tanggal di depan nomor (INV-DD-MM-YYYY-) dibiarkan, hanya angka urutan di belakangnya yang diganti.
 *
 * Jalankan: php artisan invoices:renumber   (menampilkan daftar perubahan dulu, lalu bertanya)
 */
class RenumberInvoices extends Command
{
    protected $signature = 'invoices:renumber {--yes : Langsung ubah tanpa bertanya}';

    protected $description = 'Susun ulang nomor invoice yang sudah ada jadi berurutan per akun (0001, 0002, ...) menurut urutan pembuatan.';

    public function handle(): int
    {
        $changes = [];

        $userIds = Invoice::whereNotNull('user_id')->distinct()->orderBy('user_id')->pluck('user_id');

        foreach ($userIds as $userId) {
            $sequence = 0;

            // Urutan pembuatan = urutan id (id naik terus setiap invoice baru dibuat).
            foreach (Invoice::where('user_id', $userId)->orderBy('id')->get(['id', 'number']) as $invoice) {
                $sequence++;

                $prefix = preg_match('/^(.*-)\d+$/', $invoice->number, $m) ? $m[1] : 'INV-' . now()->format('d-m-Y') . '-';
                $new = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

                if ($new !== $invoice->number) {
                    $changes[] = ['id' => $invoice->id, 'user' => $userId, 'old' => $invoice->number, 'new' => $new];
                }
            }
        }

        if (! $changes) {
            $this->info('Semua nomor invoice sudah berurutan. Tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->table(
            ['Akun (id)', 'Nomor lama', 'Nomor baru'],
            array_map(fn ($c) => [$c['user'], $c['old'], $c['new']], $changes),
        );
        $this->warn('Invoice yang sudah pernah diunduh atau dikirim ke pelanggan tetap membawa nomor lamanya.');

        if (! $this->option('yes') && ! $this->confirm(count($changes) . ' nomor invoice akan diubah. Lanjutkan?', false)) {
            $this->info('Dibatalkan. Tidak ada yang diubah.');

            return self::SUCCESS;
        }

        // Dua tahap di dalam satu transaksi. Nomor harus unik per akun, jadi kalau langsung diganti satu per satu,
        // nomor baru sebuah invoice bisa sementara sama dengan nomor lama invoice lain dan database menolaknya.
        // Tahap 1 memberi nomor sementara, tahap 2 memasang nomor akhir. Kalau ada yang gagal, semuanya dibatalkan.
        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                Invoice::whereKey($c['id'])->update(['number' => 'TMP-' . $c['id']]);
            }
            foreach ($changes as $c) {
                Invoice::whereKey($c['id'])->update(['number' => $c['new']]);
            }
        });

        $this->info(count($changes) . ' nomor invoice berhasil diurutkan ulang.');

        return self::SUCCESS;
    }
}
