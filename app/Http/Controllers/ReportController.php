<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Menu "Laporan": ringkasan uang masuk dan piutang milik akun yang sedang login.
 * Semua angka dihitung dari invoice milik akun ini saja.
 */
class ReportController extends Controller
{
    /** Ukuran grafik dalam satuan viewBox SVG (ikut melebar mengikuti kartu). */
    private const W = 640;
    private const H = 240;
    private const PAD = ['top' => 26, 'right' => 12, 'bottom' => 30, 'left' => 58];

    public function index(Request $request): View
    {
        $months = (int) $request->query('bulan') === 12 ? 12 : 6;
        $today = today();
        $mine = fn () => Invoice::where('user_id', Auth::id());

        // ---- Kondisi saat ini (tidak tergantung pilihan periode) ----
        $unpaid = $mine()->where('status', 'unpaid')->orderBy('due_date')->orderBy('id')->get();
        $overdue = $unpaid->filter(fn (Invoice $i) => $i->due_date->lt($today));
        $dueSoon = $unpaid->filter(fn (Invoice $i) => $i->due_date->betweenIncluded($today, $today->copy()->addDays(7)));

        // ---- Pemasukan per bulan (periode dipilih) ----
        // Invoice lunas dikelompokkan menurut bulan dibayar. Invoice lama tanpa tanggal bayar memakai tanggal terbit.
        // Dikelompokkan di PHP (bukan fungsi tanggal SQL) supaya sama di MySQL dan SQLite.
        $start = $today->copy()->startOfMonth()->subMonths($months - 1);
        $paid = $mine()->where('status', 'paid')
            ->where(fn ($q) => $q->where('paid_at', '>=', $start)
                ->orWhere(fn ($q) => $q->whereNull('paid_at')->where('issue_date', '>=', $start)))
            ->get(['id', 'number', 'customer_name', 'total', 'paid_at', 'issue_date']);

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i);
            $series[$m->format('Y-m')] = [
                'short' => $m->copy()->locale('id')->translatedFormat('M'),
                'full' => $m->copy()->locale('id')->translatedFormat('F Y'),
                'total' => 0,
                'count' => 0,
            ];
        }
        foreach ($paid as $invoice) {
            $key = ($invoice->paid_at ?? $invoice->issue_date)->format('Y-m');
            if (isset($series[$key])) {
                $series[$key]['total'] += (int) $invoice->total;
                $series[$key]['count']++;
            }
        }

        $keys = array_keys($series);
        $thisMonth = $series[$keys[$months - 1]]['total'];
        $lastMonth = $series[$keys[$months - 2]]['total'];

        // Pelanggan dengan pembayaran terbesar di periode ini.
        $topCustomers = $paid
            ->groupBy('customer_name')
            ->map(fn ($rows, $name) => ['name' => (string) $name, 'total' => (int) $rows->sum('total'), 'count' => $rows->count()])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        return view('reports.index', [
            'months' => $months,
            'kpi' => [
                'this_month' => $thisMonth,
                'this_month_label' => $series[$keys[$months - 1]]['full'],
                'delta' => $lastMonth > 0 ? (int) round(($thisMonth - $lastMonth) / $lastMonth * 100) : null,
                'last_month' => $lastMonth,
                'receivable' => (int) $unpaid->sum('total'),
                'receivable_count' => $unpaid->count(),
                'overdue' => (int) $overdue->sum('total'),
                'overdue_count' => $overdue->count(),
                'due_soon' => (int) $dueSoon->sum('total'),
                'due_soon_count' => $dueSoon->count(),
            ],
            'periodTotal' => array_sum(array_column($series, 'total')),
            'periodCount' => array_sum(array_column($series, 'count')),
            'series' => array_values($series),
            'chart' => $this->chart(array_values($series)),
            'toCollect' => $unpaid->take(8),
            'topCustomers' => $topCustomers,
            'today' => $today,
        ]);
    }

    /**
     * Posisi batang, garis bantu, dan label untuk grafik SVG. Dihitung di server, jadi grafiknya tampil tanpa
     * library JavaScript. Kembali null kalau periode ini belum ada pemasukan sama sekali.
     */
    private function chart(array $series): ?array
    {
        $max = max(array_column($series, 'total'));
        if ($max <= 0) {
            return null;
        }

        $step = self::niceStep($max / 4);
        $top = (int) (ceil($max / $step) * $step);
        $p = self::PAD;
        $plotW = self::W - $p['left'] - $p['right'];
        $plotH = self::H - $p['top'] - $p['bottom'];
        $base = $p['top'] + $plotH;
        $band = $plotW / count($series);
        $barW = min(24, $band * 0.6);   // batang paling tebal 24, sisanya ruang kosong

        $ticks = [];
        for ($v = 0; $v <= $top; $v += $step) {
            $ticks[] = ['y' => round($base - $v / $top * $plotH, 1), 'label' => self::compact((int) $v)];
        }

        // Angka langsung di ujung batang hanya untuk bulan terbesar dan bulan ini (sisanya lewat sumbu, tooltip, dan tabel).
        $last = count($series) - 1;
        $maxIndex = array_search($max, array_column($series, 'total'), true);

        $bars = [];
        foreach ($series as $i => $s) {
            $h = $s['total'] / $top * $plotH;
            $x = $p['left'] + $band * $i + ($band - $barW) / 2;
            $y = $base - $h;
            $bars[] = $s + [
                'x' => round($x, 1),
                'w' => round($barW, 1),
                'y' => round($y, 1),
                'cx' => round($x + $barW / 2, 1),
                'band_x' => round($p['left'] + $band * $i, 1),
                'band_w' => round($band, 1),
                'path' => $h > 0 ? self::columnPath($x, $y, $barW, $base) : null,
                'show_value' => $s['total'] > 0 && ($i === $last || $i === $maxIndex),
                'value_label' => self::compact($s['total']),
            ];
        }

        return [
            'w' => self::W, 'h' => self::H, 'base' => $base,
            'left' => $p['left'], 'right' => self::W - $p['right'], 'top' => $p['top'],
            'ticks' => $ticks, 'bars' => $bars, 'label_y' => self::H - 10,
        ];
    }

    /** Batang dengan ujung atas membulat 4 dan alas rata di garis dasar. */
    private static function columnPath(float $x, float $y, float $w, float $base): string
    {
        $r = min(4, $w / 2, $base - $y);
        $f = fn ($n) => round($n, 1);

        return sprintf(
            'M%s %s V%s Q%s %s %s %s H%s Q%s %s %s %s V%s Z',
            $f($x), $f($base), $f($y + $r), $f($x), $f($y), $f($x + $r), $f($y),
            $f($x + $w - $r), $f($x + $w), $f($y), $f($x + $w), $f($y + $r), $f($base)
        );
    }

    /** Langkah garis bantu yang enak dibaca: 1, 2, 2,5, atau 5 kali 10 pangkat n. */
    private static function niceStep(float $raw): float
    {
        $mag = 10 ** floor(log10(max($raw, 1)));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $mag >= $raw) {
                return $m * $mag;
            }
        }

        return 10 * $mag;
    }

    /** Angka ringkas untuk sumbu: 0, 500 rb, 1,5 jt, 2 M. */
    public static function compact(int $v): string
    {
        // Sampai 2 angka di belakang koma, nol di ujung dibuang: 1,25 jt / 1,5 jt / 2 jt.
        $fmt = fn (float $n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');

        return match (true) {
            $v >= 1_000_000_000 => $fmt($v / 1_000_000_000) . ' M',
            $v >= 1_000_000 => $fmt($v / 1_000_000) . ' jt',
            $v >= 1_000 => $fmt($v / 1_000) . ' rb',
            default => (string) $v,
        };
    }
}
