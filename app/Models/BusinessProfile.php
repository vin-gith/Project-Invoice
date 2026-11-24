<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BusinessProfile extends Model
{
    protected $fillable = [
        'name', 'address', 'email', 'phone',
        'invoice_title', 'bank_name', 'bank_account', 'bank_holder', 'footer_note',
        'pdf_template', 'pdf_font', 'pdf_color',
    ];

    /** Pilihan template PDF: kode => [nama, keterangan]. */
    public const TEMPLATES = [
        'modern' => ['Modern', 'Judul berwarna, tabal dengan header berwarna.'],
        'klasik' => ['Klasik', 'Kop surat di tengah, tabal bergaris penuh, ada kolom tanda tangan.'],
        'minimal' => ['Minimalist', 'Bersih dan lega, garis tipis, tanpa blok warna besar.'],
    ];

    /** Pilihan font PDF: kode => [nama, font-family untuk PDF, font-family untuk contoh di layar]. */
    public const FONTS = [
        'sans' => ['DejaVu Sans (modern)', "'DejaVu Sans', sans-serif", 'system-ui, sans-serif'],
        'serif' => ['DejaVu Serif (formal)', "'DejaVu Serif', serif", 'Georgia, serif'],
        'helvetica' => ['Helvetica (ramping)', "Helvetica, Arial, sans-serif", 'Arial, Helvetica, sans-serif'],
        'times' => ['Times (klasik)', "'Times New Roman', Times, serif", "'Times New Roman', Times, serif"],
        'courier' => ['Courier (mesin ketik)', "Courier, 'Courier New', monospace", "'Courier New', monospace"],
    ];

    /** Pilihan warna aksen PDF: kode => [nama, hex]. */
    public const COLORS = [
        'biru' => ['Biru', '#1d4ed8'],
        'hijau' => ['Hijau', '#15803d'],
        'cokelat' => ['Cokelat', '#9a3f0c'],
        'merah' => ['Merah', '#b91c1c'],
        'ungu' => ['Ungu', '#6d28d9'],
        'hitam' => ['Hitam', '#1f2937'],
    ];

    /** Tabel sudah punya kolom pemilik (migrasi terakhir sudah dijalankan)? Dicek sekali per request. */
    private static function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('business_profiles') && Schema::hasColumn('business_profiles', 'user_id');
    }

    /**
     * Data usaha milik sebuah akun, kalau sudah pernah disimpan (null kalau belum).
     * $userId kosong = akun yang sedang login. Diisi pemilik invoice saat PDF dibuka pelanggan lewat link
     * WhatsApp (tanpa login), supaya tampilannya tetap memakai data usaha dan template si penagih.
     */
    public static function stored(?int $userId = null): ?self
    {
        $userId ??= Auth::id();

        return static::ready() && $userId ? static::where('user_id', $userId)->first() : null;
    }

    /**
     * Data usaha yang dipakai di halaman invoice dan PDF.
     * Kalau belum pernah diisi (atau tabelnya belum dimigrasi), dipakai nilai bawaan dari config/invoicely.php.
     */
    public static function current(?int $userId = null): self
    {
        $profile = static::stored($userId);
        $profile ??= new static();

        foreach (['name', 'address', 'email', 'phone'] as $field) {
            if (blank($profile->{$field})) {
                $profile->setAttribute($field, config("invoicely.company.$field"));
            }
        }

        return $profile;
    }

    /** Campur warna dengan putih (0 = warna asli, 1 = putih) untuk latar lembut di PDF. */
    private static function tint(string $hex, float $toWhite): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $mix = fn (int $v) => (int) round($v + (255 - $v) * $toWhite);

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    /** Kolom pengaturan tampilan yang boleh dicoba sementara lewat pratinjau (menu Template invoice). */
    public const DESIGN_FIELDS = [
        'invoice_title', 'pdf_template', 'pdf_font', 'pdf_color',
        'bank_name', 'bank_account', 'bank_holder', 'footer_note',
    ];

    /**
     * Semua pengaturan tampilan PDF untuk akun yang sedang login, sudah dalam bentuk siap pakai di template.
     * Nilai kosong atau tidak dikenal otomatis memakai bawaan (Modern, DejaVu Sans, Biru).
     *
     * $override: nilai sementara yang menimpa pengaturan tersimpan HANYA untuk pembuatan PDF ini
     * (tidak ditulis ke database). Dipakai pratinjau supaya template bisa dicoba dulu sebelum disimpan.
     * $userId: pemilik pengaturan (kosong = akun yang sedang login).
     */
    public static function design(array $override = [], ?int $userId = null): array
    {
        $p = static::current($userId);

        foreach (self::DESIGN_FIELDS as $field) {
            if (array_key_exists($field, $override)) {
                $p->setAttribute($field, $override[$field]);
            }
        }

        $template = array_key_exists($p->pdf_template, self::TEMPLATES) ? $p->pdf_template : 'modern';
        $font = self::FONTS[$p->pdf_font][1] ?? self::FONTS['sans'][1];
        $color = (self::COLORS[$p->pdf_color] ?? self::COLORS['biru'])[1];

        return [
            'title' => $p->invoice_title ?: 'INVOICE',
            'template' => $template,
            'font' => $font,
            'color' => $color,
            'tint' => self::tint($color, 0.9),
            'soft' => self::tint($color, 0.75),
            // 'logo' dan 'watermark' diisi InvoicePdf::make dari logo milik invoice itu sendiri.
            'bank_name' => $p->bank_name,
            'bank_account' => $p->bank_account,
            'bank_holder' => $p->bank_holder,
            'footer' => $p->footer_note,
        ];
    }
}
