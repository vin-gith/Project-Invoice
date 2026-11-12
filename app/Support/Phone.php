<?php

namespace App\Support;

/**
 * Nomor telepon seluler Indonesia. Awalan "+62" dipasang tetap di form,
 * jadi pengguna mengisi sisanya: diawali angka 8, total 9-12 angka (setara 08xx... dengan 10-13 digit).
 */
class Phone
{
    /** Ambil angka setelah "+62" dari nomor yang sudah tersimpan (untuk mengisi form lagi). */
    public static function rest(?string $value): string
    {
        $d = preg_replace('/\D/', '', (string) $value);

        if (str_starts_with($d, '62')) {
            $d = substr($d, 2);
        } elseif (str_starts_with($d, '0')) {
            $d = substr($d, 1);
        }

        return str_starts_with($d, '8') ? $d : '';
    }

    /** "81234567890" -> "+62 812-3456-7890" */
    public static function format(string $rest): string
    {
        return '+62 ' . substr($rest, 0, 3) . '-' . substr($rest, 3, 4) . '-' . substr($rest, 7);
    }
}
