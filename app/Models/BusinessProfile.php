<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BusinessProfile extends Model
{
    protected $fillable = [
        'name', 'address', 'email', 'phone',
        'invoice_title', 'bank_name', 'bank_account', 'bank_holder', 'footer_note',
        'pdf_template', 'pdf_font', 'pdf_color',
    ];

    /** Pilihan template PDF: kode => [nama, keterangan]. */
    public const TEMPLATES = [
        'modern' => ['Modern', 'Judul berwarna, tabel dengan header berwarna.'],
        'klasik' => ['Klasik', 'Kop surat di tengah, tabel bergaris penuh, ada kolom tanda tangan.'],
        'minimal' => ['Minimal', 'Bersih dan lega, garis tipis, tanpa blok warna besar.'],
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

    /** Data usaha milik akun yang sedang login, kalau sudah pernah disimpan (null kalau belum). */
    public static function stored(): ?self
    {
        return static::ready() && Auth::check() ? static::where('user_id', Auth::id())->first() : null;
    }

    /**
     * Data usaha yang dipakai di halaman invoice dan PDF.
     * Kalau belum pernah diisi (atau tabelnya belum dimigrasi), dipakai nilai bawaan dari config/invoicely.php.
     */
    public static function current(): self
    {
        $profile = static::stored();
        $profile ??= new static();

        foreach (['name', 'address', 'email', 'phone'] as $field) {
            if (blank($profile->{$field})) {
                $profile->setAttribute($field, config("invoicely.company.$field"));
            }
        }

        return $profile;
    }

    /** Isi logo sebagai data URI (dipakai di PDF supaya tidak bergantung pada alamat file/URL). */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('local')->exists($this->logo_path)) {
            return null;
        }

        $binary = Storage::disk('local')->get($this->logo_path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    /** Campur warna dengan putih (0 = warna asli, 1 = putih) untuk latar lembut di PDF. */
    private static function tint(string $hex, float $toWhite): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $mix = fn (int $v) => (int) round($v + (255 - $v) * $toWhite);

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    /**
     * Semua pengaturan tampilan PDF untuk akun yang sedang login, sudah dalam bentuk siap pakai di template.
     * Nilai kosong atau tidak dikenal otomatis memakai bawaan (Modern, DejaVu Sans, Biru).
     */
    public static function design(): array
    {
        $p = static::current();

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
            'logo' => $p->logoDataUri(),
            'watermark' => \App\Support\LogoImage::watermark($p->logo_path),
            'bank_name' => $p->bank_name,
            'bank_account' => $p->bank_account,
            'bank_holder' => $p->bank_holder,
            'footer' => $p->footer_note,
        ];
    }
}
