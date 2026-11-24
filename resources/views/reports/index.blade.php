@extends('layouts.app')

@use('App\Models\Invoice')

@section('title', 'Laporan')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/report.css') }}">
@endpush

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Laporan</h1>
            <p class="muted">Uang yang sudah masuk dan tagihan yang masih harus ditagih.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('invoices.create') }}">Buat invoice</a>
    </section>

    {{-- Kondisi saat ini. Tidak ikut pilihan periode di bawah. --}}
    <section class="stats">
        <div class="card stat reveal" style="--i:1">
            <span class="stat-label">Pemasukan {{ $kpi['this_month_label'] }}</span>
            <strong class="stat-value" data-count="{{ $kpi['this_month'] }}" data-format="rupiah">{{ Invoice::rupiah($kpi['this_month']) }}</strong>
            @if ($kpi['delta'] !== null)
                <span class="stat-sub {{ $kpi['delta'] > 0 ? 'is-up' : ($kpi['delta'] < 0 ? 'is-down' : '') }}">
                    {{ $kpi['delta'] > 0 ? 'Naik ' . $kpi['delta'] : ($kpi['delta'] < 0 ? 'Turun ' . abs($kpi['delta']) : 'Sama, 0') }}% dari bulan lalu
                </span>
            @else
                <span class="stat-sub">Bulan lalu {{ Invoice::rupiah($kpi['last_month']) }}</span>
            @endif
        </div>
        <div class="card stat reveal" style="--i:2">
            <span class="stat-label">Piutang (belum lunas)</span>
            <strong class="stat-value" data-count="{{ $kpi['receivable'] }}" data-format="rupiah">{{ Invoice::rupiah($kpi['receivable']) }}</strong>
            <span class="stat-sub">{{ $kpi['receivable_count'] }} invoice</span>
        </div>
        <div class="card stat reveal" style="--i:3">
            <span class="stat-label">Lewat jatuh tempo</span>
            <strong class="stat-value" data-count="{{ $kpi['overdue'] }}" data-format="rupiah">{{ Invoice::rupiah($kpi['overdue']) }}</strong>
            <span class="stat-sub {{ $kpi['overdue_count'] ? 'is-down' : '' }}">{{ $kpi['overdue_count'] }} invoice terlambat</span>
        </div>
        <div class="card stat reveal" style="--i:4">
            <span class="stat-label">Jatuh tempo 7 hari ke depan</span>
            <strong class="stat-value" data-count="{{ $kpi['due_soon'] }}" data-format="rupiah">{{ Invoice::rupiah($kpi['due_soon']) }}</strong>
            <span class="stat-sub">{{ $kpi['due_soon_count'] }} invoice</span>
        </div>
    </section>

    {{-- Pilihan periode: berlaku untuk grafik dan pelanggan teratas di bawahnya. --}}
    <div class="report-filter reveal" style="--i:5">
        <span class="muted">Periode</span>
        <nav class="pills" aria-label="Periode laporan">
            <a class="pill {{ $months === 6 ? 'is-active' : '' }}" href="{{ route('reports.index') }}" @if ($months === 6) aria-current="page" @endif>6 bulan</a>
            <a class="pill {{ $months === 12 ? 'is-active' : '' }}" href="{{ route('reports.index', ['bulan' => 12]) }}" @if ($months === 12) aria-current="page" @endif>12 bulan</a>
        </nav>
    </div>

    <div class="report-grid">
        <section class="card report-card reveal" style="--i:6" aria-labelledby="chartTitle">
            <header class="report-head">
                <h2 id="chartTitle">Pemasukan per bulan</h2>
                <p class="muted" id="chartDesc">
                    Invoice lunas menurut bulan dibayar. Total {{ $months }} bulan:
                    <strong class="text">{{ Invoice::rupiah($periodTotal) }}</strong> dari {{ $periodCount }} invoice.
                </p>
            </header>

            @if ($chart)
                {{-- Grafik SVG dari server (posisi dihitung di ReportController::chart). Arahkan kursor atau Tab ke batang untuk detail. --}}
                <div class="chart" data-chart>
                    <svg class="chart-svg" viewBox="0 0 {{ $chart['w'] }} {{ $chart['h'] }}" role="group" aria-labelledby="chartTitle" aria-describedby="chartDesc">
                        <g class="chart-grid" aria-hidden="true">
                            @foreach ($chart['ticks'] as $tick)
                                <line x1="{{ $chart['left'] }}" x2="{{ $chart['right'] }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" />
                                <text x="{{ $chart['left'] - 10 }}" y="{{ $tick['y'] + 4 }}" text-anchor="end">{{ $tick['label'] }}</text>
                            @endforeach
                        </g>
                        @foreach ($chart['bars'] as $bar)
                            <g class="chart-bar" tabindex="0" role="img"
                               aria-label="{{ $bar['full'] }}: {{ Invoice::rupiah($bar['total']) }} dari {{ $bar['count'] }} invoice"
                               data-tip-label="{{ $bar['full'] }} · {{ $bar['count'] }} invoice"
                               data-tip-value="{{ Invoice::rupiah($bar['total']) }}">
                                {{-- Area sentuh selebar satu bulan penuh, lebih besar dari batangnya. --}}
                                <rect class="chart-hit" x="{{ $bar['band_x'] }}" y="{{ $chart['top'] }}" width="{{ $bar['band_w'] }}" height="{{ $chart['base'] - $chart['top'] }}" rx="6" />
                                @if ($bar['path'])
                                    <path class="chart-col" d="{{ $bar['path'] }}" />
                                @endif
                                @if ($bar['show_value'])
                                    <text class="chart-value" x="{{ $bar['cx'] }}" y="{{ $bar['y'] - 8 }}" text-anchor="middle" aria-hidden="true">{{ $bar['value_label'] }}</text>
                                @endif
                                <text class="chart-x" x="{{ $bar['cx'] }}" y="{{ $chart['label_y'] }}" text-anchor="middle" aria-hidden="true">{{ $bar['short'] }}</text>
                            </g>
                        @endforeach
                        <line class="chart-base" x1="{{ $chart['left'] }}" x2="{{ $chart['right'] }}" y1="{{ $chart['base'] }}" y2="{{ $chart['base'] }}" aria-hidden="true" />
                    </svg>
                    <div class="chart-tip" role="presentation" hidden>
                        <strong class="chart-tip-value"></strong>
                        <span class="chart-tip-label"></span>
                    </div>
                </div>

                <details class="chart-table">
                    <summary>Lihat sebagai tabel</summary>
                    <div class="table-wrap">
                        <table class="table table-plain">
                            <thead><tr><th>Bulan</th><th class="num">Invoice lunas</th><th class="num">Pemasukan</th></tr></thead>
                            <tbody>
                                @foreach ($series as $row)
                                    <tr>
                                        <td data-label="Bulan">{{ $row['full'] }}</td>
                                        <td data-label="Invoice lunas" class="num">{{ $row['count'] }}</td>
                                        <td data-label="Pemasukan" class="num strong">{{ Invoice::rupiah($row['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @else
                <div class="empty">
                    <strong>Belum ada invoice lunas di periode ini.</strong>
                    <span class="muted">Tandai invoice sebagai lunas begitu pembayaran diterima, nanti muncul di sini.</span>
                </div>
            @endif
        </section>

        <section class="card report-card reveal" style="--i:7" aria-labelledby="topTitle">
            <header class="report-head">
                <h2 id="topTitle">Pelanggan teratas</h2>
                <p class="muted">Pembayaran terbesar, {{ $months }} bulan terakhir.</p>
            </header>
            @forelse ($topCustomers as $row)
                <div class="top-row">
                    <span class="top-name" translate="no">{{ $row['name'] }}</span>
                    <strong class="top-total">{{ Invoice::rupiah($row['total']) }}</strong>
                    <span class="top-count muted">{{ $row['count'] }} invoice</span>
                </div>
            @empty
                <div class="empty"><span class="muted">Belum ada pembayaran di periode ini.</span></div>
            @endforelse
        </section>
    </div>

    <section class="card report-card reveal" style="--i:8" aria-labelledby="collectTitle">
        <header class="report-head">
            <h2 id="collectTitle">Perlu ditagih</h2>
            <p class="muted">Invoice belum lunas, dari jatuh tempo paling dekat. Kirim pengingat lewat WhatsApp langsung dari sini.</p>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Pelanggan</th>
                        <th>Jatuh tempo</th>
                        <th>Keterangan</th>
                        <th class="num">Total</th>
                        <th class="actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($toCollect as $invoice)
                        @php
                            $days = (int) $today->diffInDays($invoice->due_date, false);
                            $note = match (true) {
                                $days < 0 => 'Lewat ' . abs($days) . ' hari',
                                $days === 0 => 'Jatuh tempo hari ini',
                                default => $days . ' hari lagi',
                            };
                        @endphp
                        <tr class="row-reveal" style="--i:{{ $loop->index }}">
                            <td data-label="Nomor" class="nowrap"><a class="link strong" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                            <td data-label="Pelanggan" translate="no">{{ $invoice->customer_name }}</td>
                            <td data-label="Jatuh tempo">{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td data-label="Keterangan">
                                <span class="badge badge-{{ $invoice->statusKey() }}">{{ $note }}</span>
                            </td>
                            <td data-label="Total" class="num strong">{{ Invoice::rupiah($invoice->total) }}</td>
                            <td data-label="Aksi" class="actions-cell">
                                <div class="actions">
                                    <a class="btn btn-sm btn-ghost" href="{{ route('invoices.whatsapp', $invoice) }}" target="_blank" rel="noopener" data-no-transition>
                                        {{ $days < 0 ? 'Kirim pengingat' : 'Kirim WhatsApp' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="6">
                                <div class="empty">
                                    <strong>Semua invoice sudah lunas.</strong>
                                    <span class="muted">Tidak ada yang perlu ditagih saat ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($kpi['receivable_count'] > $toCollect->count())
            <p class="report-more"><a class="link" href="{{ route('invoices.index', ['status' => 'unpaid']) }}">Lihat semua {{ $kpi['receivable_count'] }} invoice belum lunas</a></p>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        // Tooltip grafik: muncul saat kursor di atas batang atau batang dipilih dengan Tab.
        // Isinya diambil dari atribut data-tip-* dan ditulis lewat textContent (bukan innerHTML).
        (function () {
            var chart = document.querySelector('[data-chart]');
            if (!chart) { return; }
            var tip = chart.querySelector('.chart-tip');
            var tipValue = tip.querySelector('.chart-tip-value');
            var tipLabel = tip.querySelector('.chart-tip-label');

            function show(bar) {
                tipValue.textContent = bar.getAttribute('data-tip-value');
                tipLabel.textContent = bar.getAttribute('data-tip-label');
                tip.hidden = false;
                bar.classList.add('is-active');

                var box = chart.getBoundingClientRect();
                var mark = (bar.querySelector('.chart-col') || bar.querySelector('.chart-hit')).getBoundingClientRect();
                var half = tip.offsetWidth / 2;
                var x = mark.left + mark.width / 2 - box.left;
                tip.style.left = Math.max(half, Math.min(box.width - half, x)) + 'px';
                tip.style.top = Math.max(0, mark.top - box.top - 10) + 'px';
            }
            function hide(bar) {
                bar.classList.remove('is-active');
                tip.hidden = true;
            }

            chart.querySelectorAll('.chart-bar').forEach(function (bar) {
                bar.addEventListener('pointerenter', function () { show(bar); });
                bar.addEventListener('pointerleave', function () { hide(bar); });
                bar.addEventListener('focus', function () { show(bar); });
                bar.addEventListener('blur', function () { hide(bar); });
            });
        })();
    </script>
@endpush
