<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Logo untuk PDF. dompdf paling aman dengan gambar yang ditanam langsung (data URI),
 * dan watermark dibuat dengan memudarkan logo di server (tidak bergantung pada CSS opacity).
 */
class LogoImage
{
    /** Logo asli sebagai data URI (untuk kop di bagian atas invoice). */
    public static function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('local')->get($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    /** Logo versi pudar (untuk watermark di tengah halaman) sebagai data URI PNG persegi. */
    public static function watermark(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $png = self::fade(Storage::disk('local')->get($path));

        return $png ? 'data:image/png;base64,' . base64_encode($png) : null;
    }

    /**
     * Logo dimasukkan ke kanvas putih persegi (diperkecil kalau perlu, proporsi tetap),
     * lalu dicampur putih sehingga tinggal sebagian kecil warnanya. $strength = 0.10 artinya
     * logo tampak 10% dari warna aslinya. Mengembalikan isi PNG, atau null kalau gambar tidak bisa dibaca / GD tidak ada.
     */
    public static function fade(string $binary, float $strength = 0.10, int $size = 600): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min($size / $w, $size / $h);
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true); // bagian transparan logo jadi putih
        imagecopyresampled($canvas, $src, intdiv($size - $tw, 2), intdiv($size - $th, 2), 0, 0, $tw, $th, $w, $h);
        imagedestroy($src);

        $white = imagecreatetruecolor($size, $size);
        imagefill($white, 0, 0, imagecolorallocate($white, 255, 255, 255));
        imagecopymerge($canvas, $white, 0, 0, 0, 0, $size, $size, (int) round((1 - $strength) * 100));
        imagedestroy($white);

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();
        imagedestroy($canvas);

        return $png ?: null;
    }
}
