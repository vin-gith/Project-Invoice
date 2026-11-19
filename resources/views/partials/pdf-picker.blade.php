{{--
    Pilih template dulu sebelum mengunduh PDF.

    Dipasang sekali di layouts/app.blade.php (hanya untuk yang sudah login). Skrip di bawah mencegat semua tautan
    "Unduh PDF" (alamatnya /invoices/{id}/pdf), baik di daftar invoice maupun di halaman detail, lalu menampilkan
    pilihan template. Setelah dipilih, PDF diunduh lewat alamat yang sama dengan tambahan ?template=kode.

    Pilihan ini HANYA untuk unduhan itu. Template yang tersimpan di menu "Template invoice" tidak diubah.
    Daftar template dibaca dari BusinessProfile::TEMPLATES, jadi kalau nama atau isinya diubah di sana, ikut berubah di sini.
    Kalau JavaScript mati, tautannya tetap jalan biasa dan memakai template yang tersimpan.
--}}
@php
    $pickerTemplates = \App\Models\BusinessProfile::TEMPLATES;
    // Pilihan awal di kotak: template yang tersimpan (sama dengan yang dipakai PDF kalau tidak memilih apa-apa).
    $pickerSaved = \App\Models\BusinessProfile::current()->pdf_template;
    if (! array_key_exists($pickerSaved, $pickerTemplates)) {
        $pickerSaved = array_key_exists('modern', $pickerTemplates) ? 'modern' : array_key_first($pickerTemplates);
    }
@endphp

<div class="modal" id="pdfModal" hidden>
    <div class="modal-scrim" data-pdf-close></div>
    <div class="modal-box pdf-box" role="dialog" aria-modal="true" aria-labelledby="pdfModalTitle">
        <h3 id="pdfModalTitle">Pilih template PDF</h3>
        <p>
            <span id="pdfNoWrap" hidden><strong id="pdfNo" translate="no"></strong> &middot; </span>Template ini hanya dipakai untuk unduhan kali ini. Pengaturan di menu Template invoice tidak berubah.
        </p>

        <div class="opt-grid pdf-opts">
            @foreach ($pickerTemplates as $code => [$label, $desc])
                <label class="opt">
                    <input type="radio" name="pdf_pick" value="{{ $code }}" {{ $code === $pickerSaved ? 'checked' : '' }}>
                    <span class="thumb thumb-{{ $code }}" aria-hidden="true">
                        <i></i><i></i><i></i><i></i>
                    </span>
                    <strong>{{ $label }}</strong>
                    <small>{{ $desc }}</small>
                </label>
            @endforeach
        </div>

        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" data-pdf-close>Batal</button>
            <button type="button" class="btn btn-primary" id="pdfGo">Unduh PDF</button>
        </div>
    </div>
</div>

<style>
    /* Kotak lebih lebar dari modal konfirmasi biasa supaya tiga kartu template muat sejajar. */
    .pdf-box { width: min(680px, 100%); max-height: calc(100vh - 32px); max-height: calc(100dvh - 32px); overflow-y: auto; }
    .pdf-box h3 { margin: 0; }
    .pdf-box > p { margin: 6px 0 18px; }
    .pdf-box > p strong { color: var(--text); }
    .pdf-opts { margin-bottom: 22px; }

    /* Layar sempit: kartu jadi baris (gambar kecil di kiri, nama dan keterangan di kanan). */
    @media (max-width: 600px) {
        .pdf-opts { grid-template-columns: 1fr; gap: 8px; }
        .pdf-opts .opt { display: grid; grid-template-columns: 52px 1fr; column-gap: 12px; row-gap: 2px; align-items: center; padding: 10px; }
        .pdf-opts .opt:hover { transform: none; }
        .pdf-opts .thumb { grid-row: 1 / span 2; width: 52px; height: 62px; margin: 0; }
        .pdf-opts .opt small { grid-column: 2; }
        .pdf-box .modal-actions .btn { flex: 1; }
    }
</style>

<script>
    (function () {
        'use strict';

        var modal = document.getElementById('pdfModal');
        var go = document.getElementById('pdfGo');
        var noBox = document.getElementById('pdfNo');
        var noWrap = document.getElementById('pdfNoWrap');
        if (!modal || !go) { return; }

        var pendingUrl = null;
        var lastFocus = null;
        var closing = null;

        // Tautan unduh PDF = alamat se-situs yang berakhiran /invoices/{angka}/pdf.
        function pdfLink(el) {
            var a = el && el.closest ? el.closest('a[href]') : null;
            if (!a) { return null; }
            var u;
            try { u = new URL(a.getAttribute('href'), window.location.href); } catch (err) { return null; }
            if (u.origin !== window.location.origin) { return null; }
            return /\/invoices\/\d+\/pdf\/?$/.test(u.pathname) ? { a: a, url: u } : null;
        }

        // Nomor invoice yang sedang diunduh (baris di daftar, atau judul di halaman detail), buat info kecil di kotak.
        function invoiceNumber(a) {
            var row = a.closest('tr');
            var cell = row ? row.querySelector('td[data-label="Nomor"]') : null;
            var heading = document.querySelector('main h1');
            var text = (cell ? cell.textContent : (heading ? heading.textContent : '')).trim();
            return /^INV-/i.test(text) ? text : '';
        }

        function openPicker(link) {
            pendingUrl = link.url;
            lastFocus = link.a;
            var no = invoiceNumber(link.a);
            noBox.textContent = no;
            noWrap.hidden = !no;

            clearTimeout(closing);
            modal.hidden = false;
            // Dua frame supaya transisi CSS jalan dari keadaan awal.
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { modal.classList.add('open'); });
            });
            setTimeout(function () { go.focus(); }, 60);
        }

        function closePicker(restoreFocus) {
            modal.classList.remove('open');
            clearTimeout(closing);
            closing = setTimeout(function () { modal.hidden = true; }, 320);
            pendingUrl = null;
            if (restoreFocus && lastFocus && lastFocus.focus) { lastFocus.focus(); }
        }

        document.addEventListener('click', function (e) {
            // Biarkan klik dengan Ctrl/Shift/Cmd (buka di tab baru) atau klik kanan bekerja seperti biasa.
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
            var link = pdfLink(e.target);
            if (!link) { return; }
            e.preventDefault();
            openPicker(link);
        });

        modal.addEventListener('click', function (e) {
            if (e.target.hasAttribute('data-pdf-close')) { closePicker(true); }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) { closePicker(true); }
        });

        go.addEventListener('click', function () {
            if (!pendingUrl) { return; }
            var picked = modal.querySelector('input[name="pdf_pick"]:checked');
            var url = new URL(pendingUrl.href);
            if (picked) { url.searchParams.set('template', picked.value); }
            var target = url.toString();
            closePicker(true);
            // Alamat ini membalas file unduhan, jadi halaman tidak berpindah.
            window.location.href = target;
        });
    })();
</script>
