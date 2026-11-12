@php
    $items = old('items', $invoice
        ? $invoice->items->map(fn ($i) => ['description' => $i->description, 'qty' => $i->qty, 'price' => $i->price])->all()
        : [['description' => '', 'qty' => 1, 'price' => '']]);

    // Data penagih: invoice yang sedang diubah memakai datanya sendiri, invoice baru memakai "Data usaha".
    // Kalau belum ada yang tersimpan, kolom dibiarkan kosong supaya contoh (placeholder) terlihat.
    $stored = \App\Models\BusinessProfile::stored();
    $seller = (object) [
        'name' => $invoice?->seller_name ?? $stored?->name,
        'address' => $invoice?->seller_address ?? $stored?->address,
        'email' => $invoice?->seller_email ?? $stored?->email,
        'phone' => $invoice?->seller_name ? $invoice?->seller_phone : $stored?->phone,
    ];

    // Kalau server menolak data, langsung buka langkah yang berisi kolom bermasalah.
    $errorKeys = collect($errors->keys());
    $startStep = $errorKeys->contains(fn ($k) => str_starts_with($k, 'items') || in_array($k, ['tax_percent', 'notes'], true)) ? 2
        : ($errorKeys->contains(fn ($k) => str_starts_with($k, 'customer_') || in_array($k, ['issue_date', 'due_date'], true)) ? 1 : 0);
@endphp

<form id="invoiceForm" method="POST" action="{{ $action }}" novalidate data-start-step="{{ $startStep }}">
    @csrf
    @if ($invoice)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-error reveal">
            <strong>Ada yang perlu dibenahi:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card form-card reveal" style="--i:1">
        <ol class="steps" id="steps">
            <li class="step is-active" data-step="0"><span class="step-no">1</span><span>Data penagih</span></li>
            <li class="step" data-step="1"><span class="step-no">2</span><span>Data yang ditagih</span></li>
            <li class="step" data-step="2"><span class="step-no">3</span><span>Barang/jasa dan pajak</span></li>
        </ol>
        <div class="steps-bar"><div class="steps-fill" id="stepsFill"></div></div>

        <div class="slides">
            <div class="slides-track" id="slidesTrack">

                {{-- Langkah 1: penagih (pihak yang menerbitkan invoice) --}}
                <div class="slide" data-slide="0">
                    <p class="muted slide-hint">Terisi otomatis dari menu Data usaha. Boleh diubah khusus untuk invoice ini.</p>

                    <div class="field">
                        <label for="seller_name">Nama penagih</label>
                        <input id="seller_name" type="text" name="seller_name" required maxlength="120"
                               placeholder="Contoh: Toko Maju Jaya"
                               value="{{ old('seller_name', $seller->name) }}">
                    </div>

                    <div class="field">
                        <label for="seller_address">Alamat penagih</label>
                        <input id="seller_address" type="text" name="seller_address" required maxlength="255"
                               placeholder="Contoh: Jl. Merdeka No. 12, Serang, Banten"
                               value="{{ old('seller_address', $seller->address) }}">
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="seller_email">Email penagih</label>
                            <input id="seller_email" type="email" name="seller_email" required maxlength="120"
                                   placeholder="Contoh: kontak@tokomajujaya.com"
                                   value="{{ old('seller_email', $seller->email) }}">
                        </div>
                        <div class="field">
                            <label for="seller_phone">Telepon</label>
                            @include('partials.phone-field', [
                                'id' => 'seller_phone',
                                'name' => 'seller_phone',
                                'value' => old('seller_phone', \App\Support\Phone::rest($seller->phone)),
                            ])
                        </div>
                    </div>
                    @include('partials.logo-field')
                </div>

                {{-- Langkah 2: yang ditagih (pelanggan) dan tanggal --}}
                <div class="slide" data-slide="1">
                    <div class="grid-2">
                        <div class="field">
                            <label for="customer_name">Nama pelanggan</label>
                            <input id="customer_name" type="text" name="customer_name" required maxlength="120"
                                   placeholder="Contoh: PT Sumber Makmur"
                                   value="{{ old('customer_name', $invoice->customer_name ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="customer_email">Email</label>
                            <input id="customer_email" type="email" name="customer_email" maxlength="120"
                                   placeholder="Contoh: pembelian@sumbermakmur.co.id"
                                   value="{{ old('customer_email', $invoice->customer_email ?? '') }}">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="customer_address">Alamat</label>
                            <input id="customer_address" type="text" name="customer_address" maxlength="255"
                                   placeholder="Contoh: Jl. Veteran No. 5, Cilegon, Banten"
                                   value="{{ old('customer_address', $invoice->customer_address ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="customer_phone">Telepon</label>
                            @include('partials.phone-field', [
                                'id' => 'customer_phone',
                                'name' => 'customer_phone',
                                'value' => old('customer_phone', \App\Support\Phone::rest($invoice?->customer_phone)),
                            ])
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="field">
                            <label for="issue_date">Tanggal terbit</label>
                            <input id="issue_date" type="date" name="issue_date" required
                                   value="{{ old('issue_date', optional($invoice?->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                        </div>
                        <div class="field">
                            <label for="due_date">Jatuh tempo</label>
                            <input id="due_date" type="date" name="due_date" required
                                   value="{{ old('due_date', optional($invoice?->due_date)->format('Y-m-d') ?? now()->addDays(14)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                {{-- Langkah 3: barang/jasa, harga, pajak --}}
                <div class="slide" data-slide="2">
                    <div class="items-head">
                        <h3>Barang/jasa</h3>
                        <button type="button" class="btn btn-ghost" id="addItem">Tambah item</button>
                    </div>

                    <div id="items">
                        @foreach ($items as $i => $item)
                            @include('invoices._item_row', ['index' => $i, 'item' => $item])
                        @endforeach
                    </div>

                    <div class="grid-2 totals-grid">
                        <div>
                            <div class="field">
                                <label for="tax_percent">Pajak (%)</label>
                                <input id="tax_percent" type="number" name="tax_percent" min="0" max="100" step="0.01" required placeholder="Contoh: 11"
                                       value="{{ old('tax_percent', $invoice->tax_percent ?? 11) }}">
                            </div>
                            <div class="field">
                                <label for="notes">Catatan (opsional)</label>
                                <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="Contoh: Pembayaran paling lambat 7 hari setelah invoice diterima.">{{ old('notes', $invoice->notes ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="sum-box">
                            <div class="sum-line"><span>Subtotal</span><strong data-sum="sub">Rp 0</strong></div>
                            <div class="sum-line"><span>Pajak</span><strong data-sum="tax">Rp 0</strong></div>
                            <div class="sum-line sum-total"><span>Total tagihan</span><strong data-sum="total">Rp 0</strong></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="{{ $cancelUrl }}" id="cancelBtn">Batal</a>
            <button type="button" class="btn btn-ghost" id="prevBtn" hidden>Kembali</button>
            <span class="spacer"></span>
            <button type="button" class="btn btn-primary" id="nextBtn">Lanjut</button>
            <button type="submit" class="btn btn-primary" id="saveBtn" hidden>{{ $saveLabel }}</button>
        </div>
    </div>

    {{-- Template baris item untuk tombol "Tambah item" (diisi lewat JS) --}}
    <template id="itemTpl">
        @include('invoices._item_row', ['index' => '__INDEX__', 'item' => ['description' => '', 'qty' => 1, 'price' => '']])
    </template>
</form>
