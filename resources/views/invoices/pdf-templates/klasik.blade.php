@use('App\Models\Invoice')
@use('App\Support\Terbilang')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @@page { margin: 34px 40px 38px; }
        body { font-family: {!! $design['font'] !!}; font-size: 11px; color: #111111; }
        p { margin: 0 0 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .r { text-align: right; }
        .c { text-align: center; }
        .muted { color: #555555; }
        .kop-name { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .title { font-size: 20px; font-weight: bold; letter-spacing: 3px; color: {{ $c }}; }
        .items th { background: {{ $soft }}; border: 1px solid #333333; padding: 7px 6px; font-size: 10px; text-align: center; }
        .items td { border: 1px solid #333333; padding: 6px; }
        .box { border: 1px solid #333333; padding: 8px 10px; }
    </style>
</head>
<body>
    @include('invoices.pdf-templates._watermark')
    {{-- Kop surat --}}
    <table>
        <tr>
            <td style="width:{{ $design['logo'] ? '90px' : '0' }};">
                @if ($design['logo'])
                    <img src="{{ $design['logo'] }}" style="height:60px;">
                @endif
            </td>
            <td class="c">
                <p class="kop-name">{{ $biz->name }}</p>
                <p>{{ $biz->address }}</p>
                <p class="muted">{{ $contact }}</p>
            </td>
            <td style="width:{{ $design['logo'] ? '90px' : '0' }};"></td>
        </tr>
    </table>
    <div style="border-top:3px double #111111; margin:10px 0 16px;"></div>

    <div class="c" style="margin-bottom:16px;">
        <span class="title">{{ $design['title'] }}</span>
        <p class="muted" style="margin-top:3px;">No. {{ $invoice->number }}</p>
    </div>

    <table>
        <tr>
            <td style="width:55%; padding-right:12px;">
                <p>Kepada Yth.</p>
                <p style="font-size:12px;"><strong>{{ $invoice->customer_name }}</strong></p>
                @if ($invoice->customer_address)<p>{{ $invoice->customer_address }}</p>@endif
                @if ($invoice->customer_email)<p>{{ $invoice->customer_email }}</p>@endif
                @if ($invoice->customer_phone)<p>{{ $invoice->customer_phone }}</p>@endif
            </td>
            <td>
                <table>
                    <tr><td style="width:38%;">No. Invoice</td><td style="width:4%;">:</td><td>{{ $invoice->number }}</td></tr>
                    <tr><td>Tanggal</td><td>:</td><td>{{ $tgl($invoice->issue_date) }}</td></tr>
                    <tr><td>Jatuh tempo</td><td>:</td><td><strong>{{ $tgl($invoice->due_date) }}</strong></td></tr>
                    <tr><td>Status</td><td>:</td><td style="color:{{ $stColor }};"><strong>{{ $stText }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top:16px;">
        <thead>
            <tr>
                <th style="width:6%;">No</th>
                <th>Nama barang / jasa</th>
                <th style="width:10%;">Jumlah</th>
                <th style="width:20%;">Harga satuan</th>
                <th style="width:21%;">Jumlah harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="r">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="r">{{ Invoice::rupiah($item->price) }}</td>
                    <td class="r">{{ Invoice::rupiah($item->line_total) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="r">Subtotal</td>
                <td class="r">{{ Invoice::rupiah($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="r">Pajak ({{ $pct }}%)</td>
                <td class="r">{{ Invoice::rupiah($invoice->tax_amount) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="r" style="font-size:12px;"><strong>TOTAL</strong></td>
                <td class="r" style="font-size:12px; background:{{ $soft }};"><strong>{{ Invoice::rupiah($invoice->total) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="box" style="margin-top:12px;">
        <span class="muted">Terbilang:</span> <strong><em>{{ Terbilang::rupiah((int) $invoice->total) }}</em></strong>
    </div>

    <table style="margin-top:14px;">
        <tr>
            <td style="width:58%; padding-right:14px;">
                @if ($hasBank)
                    <p><strong>Pembayaran ditransfer ke:</strong></p>
                    @if ($design['bank_name'])<p>{{ $design['bank_name'] }}</p>@endif
                    @if ($design['bank_account'])<p>No. rekening: <strong>{{ $design['bank_account'] }}</strong></p>@endif
                    @if ($design['bank_holder'])<p>a.n. {{ $design['bank_holder'] }}</p>@endif
                @endif
                @if ($invoice->notes)
                    <p style="margin-top:8px;"><strong>Catatan:</strong> {{ $invoice->notes }}</p>
                @endif
            </td>
            <td></td>
        </tr>
    </table>

    <table style="margin-top:18px;">
        <tr>
            <td class="c" style="width:50%;">
                <p>Diterima oleh,</p>
                <div style="height:56px;"></div>
                <p>( ............................. )</p>
            </td>
            <td class="c">
                <p>Hormat kami,</p>
                <div style="height:56px;"></div>
                <p>( <strong>{{ $biz->name }}</strong> )</p>
            </td>
        </tr>
    </table>

    @if ($design['footer'])
        <p class="c muted" style="margin-top:22px;"><em>{{ $design['footer'] }}</em></p>
    @endif
</body>
</html>
