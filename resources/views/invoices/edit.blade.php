@extends('layouts.app')

@section('title', 'Ubah ' . $invoice->number)

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Ubah invoice</h1>
            <p class="muted">{{ $invoice->number }}. Nomor dan status pembayaran tidak ikut berubah.</p>
        </div>
    </section>

    @include('invoices._form', [
        'invoice' => $invoice,
        'action' => route('invoices.update', $invoice),
        'cancelUrl' => route('invoices.show', $invoice),
        'saveLabel' => 'Simpan perubahan',
    ])
@endsection
