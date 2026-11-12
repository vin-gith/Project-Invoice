<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'tax_percent' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * Nomor invoice otomatis, format INV-DD-MM-YYYY-0001 (contoh: INV-09-11-2026-0001).
     * Urutan di-reset tiap hari dan dihitung per akun (tiap akun mulai dari 0001). Panggil di dalam DB::transaction
     * supaya dua invoice yang dibuat bersamaan tidak dapat nomor kembar
     * (kolom number juga unique sebagai pengaman terakhir).
     */
    public static function nextNumber(int $userId): string
    {
        // Pakai "-" sebagai pemisah, jangan "/", karena nomor ini juga jadi nama file PDF.
        $prefix = 'INV-' . now()->format('d-m-Y') . '-';

        $last = static::where('user_id', $userId)
            ->where('number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung ulang subtotal, pajak, dan total dari item yang tersimpan.
     * Total selalu dihitung di server, bukan percaya angka dari browser.
     */
    public function recalculate(): void
    {
        $subtotal = (int) $this->items()->sum('line_total');
        $tax = (int) round($subtotal * $this->tax_percent / 100);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
        ])->save();
    }

    /**
     * Data penagih untuk invoice ini. Invoice lama yang belum punya data penagih
     * memakai data usaha (halaman "Data usaha").
     */
    public function seller(): object
    {
        $biz = BusinessProfile::current();

        return (object) [
            'name' => $this->seller_name ?: $biz->name,
            'address' => $this->seller_address ?: $biz->address,
            'email' => $this->seller_email ?: $biz->email,
            'phone' => $this->seller_name ? $this->seller_phone : $biz->phone,
        ];
    }

    /**
     * Berkas logo penagih untuk invoice ini. Invoice lama yang belum punya data penagih sendiri
     * memakai logo dari "Data usaha".
     */
    public function sellerLogoPath(): ?string
    {
        return $this->seller_name ? $this->seller_logo_path : BusinessProfile::stored()?->logo_path;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /** paid | unpaid | overdue (dipakai untuk warna badge). */
    public function statusKey(): string
    {
        if ($this->isPaid()) {
            return 'paid';
        }

        return $this->due_date->lt(today()) ? 'overdue' : 'unpaid';
    }

    public function statusLabel(): string
    {
        return match ($this->statusKey()) {
            'paid' => 'Lunas',
            'overdue' => 'Terlambat',
            default => 'Belum lunas',
        };
    }

    public static function rupiah(int|float|string|null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
}
