{{-- Pembagi: memilih template PDF sesuai pengaturan akun (Data usaha > Tampilan PDF). Variabel di bawah dipakai semua template. --}}
@use('App\Models\Invoice')
@use('App\Support\Terbilang')
@php
    $biz = $invoice->seller();
    $c = $design['color'];
    $tint = $design['tint'];
    $soft = $design['soft'];
    $tgl = fn ($d) => $d->copy()->locale('id')->translatedFormat('d F Y');
    $pct = rtrim(rtrim(number_format($invoice->tax_percent, 2, ',', '.'), '0'), ',');
    $stColor = ['paid' => '#15803d', 'unpaid' => '#b45309', 'overdue' => '#b91c1c'][$invoice->statusKey()];
    $stText = mb_strtoupper($invoice->statusLabel());
    $hasBank = filled($design['bank_name']) || filled($design['bank_account']) || filled($design['bank_holder']);
    $contact = collect([$biz->email, $biz->phone])->filter()->implode('   |   ');
@endphp
@include('invoices.pdf-templates.' . $design['template'])
