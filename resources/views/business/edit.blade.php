@extends('layouts.app')

@section('title', 'Data Usaha')

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Data usaha</h1>
            <p class="muted">Identitas usaha. Dipakai sebagai kop di invoice dan PDF.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">Kembali ke daftar</a>
    </section>

    <div class="card reveal" style="--i:1; max-width: 760px;">
        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.update') }}">
            @csrf
            @method('PUT')

            <h3 class="set-title">Identitas usaha</h3>
            <p class="muted set-hint">Nama dan alamat ini menjadi kop di bagian atas invoice. Untuk satu invoice tertentu, isinya masih bisa diganti di langkah "Data penagih".</p>

            <div class="field">
                <label for="name">Nama usaha</label>
                <input id="name" type="text" name="name" value="{{ old('name', $stored?->name) }}" required maxlength="120" placeholder="Contoh: Toko Maju Jaya">
            </div>

            <div class="field">
                <label for="address">Alamat usaha</label>
                <input id="address" type="text" name="address" value="{{ old('address', $stored?->address) }}" required maxlength="255" placeholder="Contoh: Jl. Merdeka No. 12, Serang, Banten">
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="email">Email usaha</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $stored?->email) }}" required maxlength="120" placeholder="Contoh: kontak@tokomajujaya.com">
                </div>
                <div class="field">
                    <label for="phone">Telepon</label>
                    @include('partials.phone-field', [
                        'id' => 'phone',
                        'name' => 'phone',
                        'value' => old('phone', \App\Support\Phone::rest($stored?->phone)),
                    ])
                </div>
            </div>

            <div class="set-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a class="btn btn-ghost" href="{{ route('template.edit') }}">Atur template invoice</a>
            </div>
            <p class="muted set-hint" style="margin: 10px 0 0;">Judul, logo, template, font, warna, dan informasi pembayaran diatur di menu Template invoice.</p>
        </form>
    </div>
@endsection
