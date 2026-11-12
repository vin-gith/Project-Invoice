@use('App\Models\Invoice')
@use('App\Support\Terbilang')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @@page { margin: 34px 38px 38px; }
        body { font-family: {!! $design['font'] !!}; font-size: 11px; color: #1f2430; }
        p { margin: 0 0 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .r { text-align: right; }
        .c { text-align: center; }
        .muted { color: #6b7280; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 3px; }
        .co { font-size: 17px; font-weight: bold; color: {{ $c }}; }
        .title { font-size: 28px; font-weight: bold; letter-spacing: 2px; color: {{ $c }}; }
        .items th { background: {{ $c }}; color: #ffffff; padding: 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        .items td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .items tr.alt td { background: #f7f8fa; }
        .totals td { padding: 5px 8px; }
        .grand td { font-size: 14px; font-weight: bold; color: {{ $c }}; border-top: 2px solid {{ $c }}; padding-top: 9px; }
        .say { background: {{ $tint }}; border-left: 3px solid {{ $c }}; padding: 8px 10px; font-style: italic; }
    </style>
</head>
<body>
    @include('invoices.pdf-templates._watermark')
    {{-- Kop --}}
    <table>
        <tr>
            <td style="width:58%;">
                @if ($design['logo'])
                    <img src="{{ $design['logo'] }}" style="height:46px; margin-bottom:8px;"><br>
                @endif
                <span class="co">{{ $biz->name }}</span><br>
                <span class="muted">{{ $biz->address }}</span><br>
                <span class="muted">{{ $contact }}</span>
            </td>
            <td class="r">
                <div class="title">{{ $design['title'] }}</div>
                <p class="muted" style="margin-top:4px;">No. {{ $invoice->number }}</p>
            </td>
        </tr>
    </table>
    <div style="height:4px; background:{{ $c }}; margin:16px 0 18px;"></div>

    {{-- Pelanggan dan keterangan --}}
    <table>
        <tr>
            <td style="width:52%; padding-right:14px;">
                <div style="background:{{ $tint }}; padding:10px 12px;">
                    <p class="label">Ditagihkan kepada</p>
                    <p style="font-size:12px;"><strong>{{ $invoice->customer_name }}</strong></p>
                    @if ($invoice->customer_address)<p class="muted">{{ $invoice->customer_address }}</p>@endif
                    @if ($invoice->customer_email)<p class="muted">{{ $invoice->customer_email }}</p>@endif
                    @if ($invoice->customer_phone)<p class="muted">{{ $invoice->customer_phone }}</p>@endif
                </div>
            </td>
            <td>
                <table>
                    <tr><td class="muted" style="padding:2px 0; width:42%;">Tanggal terbit</td><td class="r" style="padding:2px 0;">{{ $tgl($invoice->issue_date) }}</td></tr>
                    <tr><td class="muted" style="padding:2px 0;">Jatuh tempo</td><td class="r" style="padding:2px 0;"><strong>{{ $tgl($invoice->due_date) }}</strong></td></tr>
                    <tr><td class="muted" style="padding:2px 0;">Status</td><td class="r" style="padding:2px 0; color:{{ $stColor }};"><strong>{{ $stText }}</strong></td></tr>
                    @if ($invoice->isPaid() && $invoice->paid_at)
                        <tr><td class="muted" style="padding:2px 0;">Dibayar</td><td class="r" style="padding:2px 0;">{{ $tgl($invoice->paid_at) }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Item --}}
    <table class="items" style="margin-top:22px;">
        <thead>
            <tr>
                <th style="width:6%;">No</th>
                <th>Deskripsi</th>
                <th class="r" style="width:11%;">Jumlah</th>
                <th class="r" style="width:20%;">Harga satuan</th>
                <th class="r" style="width:21%;">Jumlah harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="r">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="r">{{ Invoice::rupiah($item->price) }}</td>
                    <td class="r">{{ Invoice::rupiah($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Total --}}
    <table class="totals" style="width:48%; margin-left:52%; margin-top:10px;">
        <tr><td>Subtotal</td><td class="r">{{ Invoice::rupiah($invoice->subtotal) }}</td></tr>
        <tr><td>Pajak ({{ $pct }}%)</td><td class="r">{{ Invoice::rupiah($invoice->tax_amount) }}</td></tr>
        <tr class="grand"><td>Total tagihan</td><td class="r">{{ Invoice::rupiah($invoice->total) }}</td></tr>
    </table>

    <div class="say" style="margin-top:14px;"><span class="muted">Terbilang:</span> {{ Terbilang::rupiah((int) $invoice->total) }}</div>

    {{-- Pembayaran, catatan, tanda tangan --}}
    <table style="margin-top:22px;">
        <tr>
            <td style="width:60%; padding-right:16px;">
                @if ($hasBank)
                    <p class="label">Informasi pembayaran</p>
                    @if ($design['bank_name'])<p><strong>{{ $design['bank_name'] }}</strong></p>@endif
                    @if ($design['bank_account'])<p>No. rekening: <strong>{{ $design['bank_account'] }}</strong></p>@endif
                    @if ($design['bank_holder'])<p>a.n. {{ $design['bank_holder'] }}</p>@endif
                @endif
                @if ($invoice->notes)
                    <p class="label" style="margin-top:12px;">Catatan</p>
                    <p>{{ $invoice->notes }}</p>
                @endif
            </td>
            <td class="c">
                <p>Hormat kami,</p>
                <div style="height:52px;"></div>
                <p style="border-top:1px solid #9ca3af; padding-top:4px;"><strong>{{ $biz->name }}</strong></p>
            </td>
        </tr>
    </table>

    @if ($design['footer'])
        <p class="c muted" style="margin-top:26px; border-top:1px solid #e5e7eb; padding-top:10px;">{{ $design['footer'] }}</p>
    @endif
</body>
</html>
