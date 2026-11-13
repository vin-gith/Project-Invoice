@extends('layouts.app')

@use('App\Models\Invoice')

@section('title', $invoice->number)

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>{{ $invoice->number }}</h1>
            <p class="muted">Dibuat {{ $invoice->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">Kembali ke daftar</a>
    </section>

    <div class="detail-grid">
        <article class="card paper reveal" style="--i:1">
            <header class="paper-head">
                <div>
                    <p class="paper-kicker">Invoice</p>
                    <h2>{{ $invoice->number }}</h2>
                </div>
                <span class="badge badge-lg badge-{{ $invoice->statusKey() }}">{{ $invoice->statusLabel() }}</span>
            </header>

            <div class="paper-meta">
                <div>
                    <p class="meta-label">Penagih</p>
                    @php $biz = $invoice->seller(); @endphp
                    <p><strong translate="no">{{ $biz->name }}</strong></p>
                    <p class="muted">{{ $biz->address }}</p>
                    <p class="muted">{{ $biz->email }}</p>
                    @if ($biz->phone)<p class="muted">{{ $biz->phone }}</p>@endif
                </div>
                <div>
                    <p class="meta-label">Ditagihkan ke</p>
                    <p><strong translate="no">{{ $invoice->customer_name }}</strong></p>
                    @if ($invoice->customer_address)<p class="muted">{{ $invoice->customer_address }}</p>@endif
                    @if ($invoice->customer_email)<p class="muted">{{ $invoice->customer_email }}</p>@endif
                    @if ($invoice->customer_phone)<p class="muted">{{ $invoice->customer_phone }}</p>@endif
                </div>
                <div>
                    <p class="meta-label">Tanggal terbit</p>
                    <p>{{ $invoice->issue_date->format('d/m/Y') }}</p>
                    <p class="meta-label spaced">Jatuh tempo</p>
                    <p>{{ $invoice->due_date->format('d/m/Y') }}</p>
                    @if ($invoice->isPaid() && $invoice->paid_at)
                        <p class="meta-label spaced">Dibayar</p>
                        <p>{{ $invoice->paid_at->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-plain">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th class="num">Jumlah</th>
                            <th class="num">Harga satuan</th>
                            <th class="num">Jumlah harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr class="row-reveal" style="--i:{{ $loop->index }}">
                                <td data-label="Deskripsi">{{ $item->description }}</td>
                                <td data-label="Jumlah" class="num">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                <td data-label="Harga satuan" class="num">{{ Invoice::rupiah($item->price) }}</td>
                                <td data-label="Jumlah harga" class="num strong">{{ Invoice::rupiah($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="paper-totals">
                <div class="sum-line"><span>Subtotal</span><strong>{{ Invoice::rupiah($invoice->subtotal) }}</strong></div>
                <div class="sum-line"><span>Pajak ({{ rtrim(rtrim(number_format($invoice->tax_percent, 2, ',', '.'), '0'), ',') }}%)</span><strong>{{ Invoice::rupiah($invoice->tax_amount) }}</strong></div>
                <div class="sum-line sum-total"><span>Total tagihan</span><strong data-count="{{ $invoice->total }}" data-format="rupiah">{{ Invoice::rupiah($invoice->total) }}</strong></div>
            </div>

            @if ($invoice->notes)
                <div class="paper-notes">
                    <p class="meta-label">Catatan</p>
                    <p>{{ $invoice->notes }}</p>
                </div>
            @endif
        </article>

        <aside class="card side reveal" style="--i:2">
            <h3>Tindakan</h3>

            <form method="POST" action="{{ route('invoices.status', $invoice) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn {{ $invoice->isPaid() ? 'btn-ghost' : 'btn-primary' }} btn-block">
                    {{ $invoice->isPaid() ? 'Tandai belum lunas' : 'Tandai lunas' }}
                </button>
            </form>

            <a class="btn btn-ghost btn-block" href="{{ route('invoices.pdf', $invoice) }}" data-no-transition>Unduh PDF</a>
            <a class="btn btn-ghost btn-block" href="{{ route('invoices.print', $invoice) }}" target="_blank" rel="noopener" data-no-transition>Cetak</a>
            <a class="btn btn-ghost btn-block" href="{{ route('invoices.edit', $invoice) }}">Ubah invoice</a>

            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                  data-confirm="Invoice {{ $invoice->number }} akan dihapus permanen. Lanjutkan?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-ghost btn-block">Hapus invoice</button>
            </form>
        </aside>
    </div>
@endsection
