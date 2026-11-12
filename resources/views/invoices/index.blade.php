@extends('layouts.app')

@use('App\Models\Invoice')

@section('title', 'Daftar Invoice')

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Daftar invoice</h1>
            <p class="muted">Kelola faktur penjualan, pantau pembayaran, dan unduh PDF.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('invoices.create') }}">Buat invoice</a>
    </section>

    <section class="stats">
        <div class="card stat reveal" style="--i:1">
            <span class="stat-label">Total invoice</span>
            <strong class="stat-value" data-count="{{ $stats['count'] }}">{{ $stats['count'] }}</strong>
        </div>
        <div class="card stat reveal" style="--i:2">
            <span class="stat-label">Belum lunas</span>
            <strong class="stat-value" data-count="{{ $stats['unpaid_count'] }}">{{ $stats['unpaid_count'] }}</strong>
        </div>
        <div class="card stat reveal" style="--i:3">
            <span class="stat-label">Tagihan belum dibayar</span>
            <strong class="stat-value" data-count="{{ $stats['unpaid_total'] }}" data-format="rupiah">{{ Invoice::rupiah($stats['unpaid_total']) }}</strong>
        </div>
        <div class="card stat reveal" style="--i:4">
            <span class="stat-label">Sudah dibayar</span>
            <strong class="stat-value" data-count="{{ $stats['paid_total'] }}" data-format="rupiah">{{ Invoice::rupiah($stats['paid_total']) }}</strong>
        </div>
    </section>

    <section class="card reveal" style="--i:5">
        <div class="toolbar">
            {{-- data-live-search: hasil langsung berubah saat mengetik, dan rekomendasi muncul di bawah kolom (lihat app.js). --}}
            <form method="GET" action="{{ route('invoices.index') }}" class="search" data-live-search data-suggest-url="{{ route('invoices.suggest') }}">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="search-box">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nomor atau nama pelanggan" aria-label="Cari invoice"
                           autocomplete="off" role="combobox" aria-expanded="false" aria-controls="suggestList" aria-autocomplete="list">
                    <ul class="suggest" id="suggestList" role="listbox" hidden></ul>
                </div>
                <button type="submit" class="btn btn-ghost">Cari</button>
            </form>

            <div class="pills">
                <a class="pill {{ ! request('status') ? 'is-active' : '' }}" href="{{ route('invoices.index', array_filter(['q' => request('q')])) }}">Semua</a>
                <a class="pill {{ request('status') === 'unpaid' ? 'is-active' : '' }}" href="{{ route('invoices.index', array_filter(['q' => request('q'), 'status' => 'unpaid'])) }}">Belum lunas</a>
                <a class="pill {{ request('status') === 'paid' ? 'is-active' : '' }}" href="{{ route('invoices.index', array_filter(['q' => request('q'), 'status' => 'paid'])) }}">Lunas</a>
            </div>
        </div>

        <div id="invoiceResults">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Pelanggan</th>
                        <th>Terbit</th>
                        <th>Jatuh tempo</th>
                        <th class="num">Total</th>
                        <th>Status</th>
                        <th class="actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="row-reveal" style="--i:{{ $loop->index }}">
                            <td data-label="Nomor" class="nowrap">
                                <a class="link strong" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a>
                            </td>
                            <td data-label="Pelanggan">{{ $invoice->customer_name }}</td>
                            <td data-label="Terbit">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                            <td data-label="Jatuh tempo">{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td data-label="Total" class="num strong">{{ Invoice::rupiah($invoice->total) }}</td>
                            <td data-label="Status">
                                <span class="badge badge-{{ $invoice->statusKey() }}">{{ $invoice->statusLabel() }}</span>
                            </td>
                            <td data-label="Aksi" class="actions-cell">
                                <div class="actions">
                                    <a class="btn btn-sm btn-ghost" href="{{ route('invoices.show', $invoice) }}">Lihat</a>
                                    <a class="btn btn-sm btn-primary" href="{{ route('invoices.pdf', $invoice) }}" data-no-transition>Unduh PDF</a>
                                    <form method="POST" action="{{ route('invoices.status', $invoice) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $invoice->isPaid() ? 'btn-ghost' : 'btn-success' }}">
                                            {{ $invoice->isPaid() ? 'Batalkan lunas' : 'Tandai lunas' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7">
                                <div class="empty">
                                    <strong>Belum ada invoice yang cocok.</strong>
                                    <span class="muted">Coba ubah pencarian atau buat invoice baru.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="pager">
                @if ($invoices->onFirstPage())
                    <span class="btn btn-ghost is-disabled">Sebelumnya</span>
                @else
                    <a class="btn btn-ghost" href="{{ $invoices->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span class="muted">Halaman {{ $invoices->currentPage() }} dari {{ $invoices->lastPage() }}</span>

                @if ($invoices->hasMorePages())
                    <a class="btn btn-ghost" href="{{ $invoices->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="btn btn-ghost is-disabled">Berikutnya</span>
                @endif
            </div>
        @endif
        </div>
    </section>
@endsection
