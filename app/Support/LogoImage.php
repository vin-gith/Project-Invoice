<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
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

        // dompdf butuh ekstensi PHP GD untuk memasang PNG (terutama yang transparan). Tanpa GD, pembuatan PDF
        // gagal total (error 500 di Unduh, Cetak, dan pratinjau template). Jadi logo PNG dilewati dan PDF tetap jadi
        // tanpa logo. JPG tidak butuh GD. Supaya logo PNG tampil, aktifkan "extension=gd" di php.ini lalu restart server.
        if ($mime === 'image/png' && ! extension_loaded('gd')) {
            Log::warning('[Invoicely] Logo PNG tidak dipasang di PDF karena ekstensi PHP GD belum aktif (extension=gd di php.ini).', ['path' => $path]);

            return null;
        }

        // Di PDF logo cuma setinggi ±1,5 cm. Logo besar (misalnya 1254 px, ±1 MB) dulu menambah ±3 detik dan ±1 MB
        // di setiap PDF, sampai pratinjau lewat link tunnel kehabisan waktu. Diperkecil dulu, sisi terpanjang 400 px.
        $binary = self::shrink($binary, $mime, 400) ?? $binary;

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    /** Perkecil gambar supaya sisi terpanjangnya $max px (proporsi dan transparansi tetap). Null kalau tidak perlu atau tidak bisa. */
    private static function shrink(string $binary, string $mime, int $max): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return null;
        }
        $size = @getimagesizefromstring($binary);
        if (! $size || max($size[0], $size[1]) <= $max || ! ($src = @imagecreatefromstring($binary))) {
            return null;
        }

        $scale = $max / max($size[0], $size[1]);
        $w = max(1, (int) round($size[0] * $scale));
        $h = max(1, (int) round($size[1] * $scale));
        // Transparansi hanya disimpan kalau aslinya transparan: PNG transparan diproses dompdf per piksel (lebih lambat).
        $alpha = $mime === 'image/png' && (in_array(ord($binary[25] ?? "\0"), [4, 6], true) || str_contains($binary, 'tRNS'));

        $dst = imagecreatetruecolor($w, $h);
        if ($alpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);

        ob_start();
        $mime === 'image/png' ? imagepng($dst, null, 6) : imagejpeg($dst, null, 88);
        $out = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return $out ?: null;
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
