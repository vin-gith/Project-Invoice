@use('App\Models\Invoice')
@use('App\Support\Terbilang')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @@page { margin: 30px 36px 36px; }
        body { font-family: {!! $design['font'] !!}; font-size: 11px; color: #222222; }
        p { margin: 0 0 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .r { text-align: right; }
        .muted { color: #7a7f87; }
        .page { padding: 0 6px; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 2px; color: #7a7f87; margin-bottom: 4px; }
        .items th { padding: 8px 4px; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1.5px; color: #7a7f87; text-align: left; border-bottom: 1px solid #222222; }
        .items td { padding: 9px 4px; border-bottom: 1px solid #e4e5e8; }
    </style>
</head>
<body>
    @include('invoices.pdf-templates._watermark')
    {{-- Pita atas --}}
    <div style="background:#f1f2f4; padding:26px 28px 22px;">
        <table>
            <tr>
                <td>
                    <div style="font-size:19px; letter-spacing:4px; text-transform:uppercase; font-weight:bold;">{{ $biz->name }}</div>
                    <p class="muted" style="margin-top:5px;">{{ $biz->address }}</p>
                </td>
                <td class="r" style="width:30%;">
                    @if ($design['logo'])
                        <img src="{{ $design['logo'] }}" style="height:46px;">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="page">
        <table style="margin-top:28px;">
            <tr>
                <td>
                    <p class="label">Ditagihkan kepada</p>
                    <p style="font-size:12.5px;"><strong>{{ $invoice->customer_name }}</strong></p>
                    @if ($invoice->customer_address)<p class="muted">{{ $invoice->customer_address }}</p>@endif
                    @if ($invoice->customer_email)<p class="muted">{{ $invoice->customer_email }}</p>@endif
                    @if ($invoice->customer_phone)<p class="muted">{{ $invoice->customer_phone }}</p>@endif
                </td>
                <td class="r" style="width:46%;">
                    <p style="font-size:15px; letter-spacing:4px; text-transform:uppercase; color:{{ $c }}; font-weight:bold;">{{ $design['title'] }}</p>
                    <p style="margin-top:4px;"><strong>{{ $invoice->number }}</strong></p>
                    <p class="muted" style="margin-top:8px;">Terbit {{ $tgl($invoice->issue_date) }}</p>
                    <p class="muted">Jatuh tempo {{ $tgl($invoice->due_date) }}</p>
                    <p style="margin-top:6px; color:{{ $stColor }}; letter-spacing:1px;"><strong>{{ $stText }}</strong></p>
                </td>
            </tr>
        </table>

        <table class="items" style="margin-top:26px;">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="r" style="width:11%;">Jumlah</th>
                    <th class="r" style="width:21%;">Harga satuan</th>
                    <th class="r" style="width:21%;">Jumlah harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="r">{{ number_format($item->qty, 0, ',', '.') }}</td>
                        <td class="r">{{ Invoice::rupiah($item->price) }}</td>
                        <td class="r">{{ Invoice::rupiah($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="width:46%; margin-left:54%; margin-top:14px;">
            <tr><td class="muted" style="padding:4px 4px;">Subtotal</td><td class="r" style="padding:4px 4px;">{{ Invoice::rupiah($invoice->subtotal) }}</td></tr>
            <tr><td class="muted" style="padding:4px 4px;">Pajak ({{ $pct }}%)</td><td class="r" style="padding:4px 4px;">{{ Invoice::rupiah($invoice->tax_amount) }}</td></tr>
            <tr><td style="padding:10px 4px 0; border-top:1px solid #222222;"><strong>Total</strong></td><td class="r" style="padding:8px 4px 0; border-top:1px solid #222222; font-size:16px; color:{{ $c }};"><strong>{{ Invoice::rupiah($invoice->total) }}</strong></td></tr>
        </table>

        <p class="muted" style="margin-top:12px;"><em>Terbilang: {{ Terbilang::rupiah((int) $invoice->total) }}</em></p>

        <table style="margin-top:26px;">
            <tr>
                <td style="width:55%; padding-right:16px;">
                    @if ($hasBank)
                        <p class="label">Informasi pembayaran</p>
                        @if ($design['bank_name'])<p>Bank: <strong>{{ $design['bank_name'] }}</strong></p>@endif
                        @if ($design['bank_account'])<p>No. rekening: <strong>{{ $design['bank_account'] }}</strong></p>@endif
                        @if ($design['bank_holder'])<p>Atas nama: {{ $design['bank_holder'] }}</p>@endif
                    @endif
                </td>
                <td>
                    @if ($invoice->notes)
                        <p class="label">Catatan</p>
                        <p>{{ $invoice->notes }}</p>
                    @endif
                </td>
            </tr>
        </table>

        @if ($design['footer'])
            <p class="muted" style="margin-top:22px;"><em>{{ $design['footer'] }}</em></p>
        @endif
    </div>

    {{-- Pita bawah: kontak --}}
    <div style="background:#f1f2f4; padding:14px 28px; margin-top:26px;">
        <table>
            <tr>
                <td class="muted" style="font-size:10px;">{{ $biz->name }}</td>
                <td class="r muted" style="font-size:10px;">{{ $contact }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
