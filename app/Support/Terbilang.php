<?php

namespace App\Support;

/** Angka jadi kalimat rupiah, contoh: 297500 -> "Dua Ratus Sembilan Puluh Tujuh Ribu Lima Ratus Rupiah". */
class Terbilang
{
    private const DASAR = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function rupiah(int $angka): string
    {
        if ($angka <= 0) {
            return 'Nol Rupiah';
        }

        return ucwords(self::ucap($angka)) . ' Rupiah';
    }

    private static function ucap(int $n): string
    {
        $sisa = fn (int $x) => $x > 0 ? ' ' . self::ucap($x) : '';

        return match (true) {
            $n < 12 => self::DASAR[$n],
            $n < 20 => self::ucap($n - 10) . ' belas',
            $n < 100 => self::ucap(intdiv($n, 10)) . ' puluh' . $sisa($n % 10),
            $n < 200 => 'seratus' . $sisa($n - 100),
            $n < 1000 => self::ucap(intdiv($n, 100)) . ' ratus' . $sisa($n % 100),
            $n < 2000 => 'seribu' . $sisa($n - 1000),
            $n < 1_000_000 => self::ucap(intdiv($n, 1000)) . ' ribu' . $sisa($n % 1000),
            $n < 1_000_000_000 => self::ucap(intdiv($n, 1_000_000)) . ' juta' . $sisa($n % 1_000_000),
            $n < 1_000_000_000_000 => self::ucap(intdiv($n, 1_000_000_000)) . ' miliar' . $sisa($n % 1_000_000_000),
            default => self::ucap(intdiv($n, 1_000_000_000_000)) . ' triliun' . $sisa($n % 1_000_000_000_000),
        };
    }
}
