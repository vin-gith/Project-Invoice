@extends('layouts.app')

@section('title', 'Buat Invoice')

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Buat invoice</h1>
            <p class="muted">Nomor invoice dibuat otomatis saat disimpan.</p>
        </div>
    </section>

    @include('invoices._form', [
        'invoice' => null,
        'action' => route('invoices.store'),
        'cancelUrl' => route('invoices.index'),
        'saveLabel' => 'Simpan invoice',
    ])
@endsection
