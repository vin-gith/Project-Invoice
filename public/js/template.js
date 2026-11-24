/* Halaman "Template invoice": pratinjau PDF langsung.
   Setiap pilihan di form (template, font, warna, judul, bank, catatan kaki) atau pergantian invoice
   dikirim sebagai query string ke route pratinjau, lalu PDF-nya ditampilkan di iframe.
   Tidak ada yang disimpan di sini; menyimpan tetap lewat tombol "Simpan template".

   PRATINJAU-TUNNEL: PDF diambil dulu lewat fetch (Accept: application/pdf), baru ditampilkan di iframe
   dari blob. Cara ini tidak terhalang halaman peringatan tunnel (VS Code / devtunnels) dan header
   pembatas iframe, dan kalau gagal, alasannya ditulis di kotak pratinjau (bukan kotak hitam kosong).
   Pratinjau pertama juga tidak lagi menunggu event "load" milik seluruh halaman. */
(function () {
    'use strict';

    var form = document.getElementById('tplForm');
    var frame = document.getElementById('tplFrame');
    var stage = document.getElementById('tplStage');
    var select = document.getElementById('tplInvoice');
    if (!form || !frame || !stage || !select) { return; }

    var hidden = document.getElementById('tplInvoiceField');
    var meta = document.getElementById('tplMeta');
    var chip = document.getElementById('tplDirty');
    var openLink = document.getElementById('tplOpen');
    var downloadLink = document.getElementById('tplDownload');
    var refreshBtn = document.getElementById('tplRefresh');
    var baseUrl = frame.getAttribute('data-url');

    // Nama kolom yang ikut dicoba di pratinjau (sama dengan DESIGN_FIELDS di BusinessProfile).
    var FIELDS = ['invoice_title', 'pdf_template', 'pdf_font', 'pdf_color', 'bank_name', 'bank_account', 'bank_holder', 'footer_note'];

    var VIEW = '#toolbar=0&navpanes=0&view=FitH'; // hanya untuk penampil PDF Chrome (tanpa toolbar, lebar pas)
    var TIMEOUT = 45000; // PDF pertama bisa lama (font dibuat dulu), jadi lebih longgar dari 15 detik

    function values() {
        var out = {};
        FIELDS.forEach(function (name) {
            var el = form.elements[name]; // radio -> RadioNodeList, selain itu elemen biasa; dua-duanya punya .value
            out[name] = el ? el.value : '';
        });
        return out;
    }

    // Kondisi awal (yang tersimpan) untuk menandai "Belum disimpan" kalau pilihan berubah.
    var saved = JSON.stringify(values());

    function params(extra) {
        var p = new URLSearchParams();
        if (select.value) { p.set('invoice', select.value); }
        var v = values();
        FIELDS.forEach(function (name) { p.set(name, v[name]); });
        Object.keys(extra || {}).forEach(function (k) { p.set(k, extra[k]); });
        return p.toString();
    }

    function updateLinks() {
        openLink.href = baseUrl + '?' + params();
        downloadLink.href = baseUrl + '?' + params({ unduh: '1' });
        if (hidden) { hidden.value = select.value; }
        chip.hidden = JSON.stringify(values()) === saved;

        var opt = select.options[select.selectedIndex];
        if (meta) { meta.textContent = opt ? (opt.getAttribute('data-meta') || '') : ''; }
    }

    // ---- Kotak pesan di atas pratinjau (dipakai saat gagal) ----
    var noteEl = null;

    function injectStyle() {
        if (document.getElementById('tplNoteStyle')) { return; }
        var s = document.createElement('style');
        s.id = 'tplNoteStyle';
        s.textContent =
            '.tpl-note{position:absolute;inset:0;display:grid;place-content:center;justify-items:center;gap:8px;' +
            'padding:24px;text-align:center;background:var(--surface);color:var(--muted);font-size:.9rem;line-height:1.5}' +
            '.tpl-note[hidden]{display:none}' +
            '.tpl-note strong{color:var(--text);font-size:1rem}' +
            '.tpl-note p{margin:0;max-width:38ch}';
        document.head.appendChild(s);
    }

    function note(title, detail) {
        injectStyle();
        if (!noteEl) {
            noteEl = document.createElement('div');
            noteEl.className = 'tpl-note';
            noteEl.setAttribute('role', 'status');
            stage.appendChild(noteEl);
        }
        noteEl.textContent = '';
        var b = document.createElement('strong');
        b.textContent = title;
        var p = document.createElement('p');
        p.textContent = detail;
        noteEl.appendChild(b);
        noteEl.appendChild(p);
        noteEl.hidden = false;
    }

    function clearNote() { if (noteEl) { noteEl.hidden = true; } }

    var failSafe;
    function finish() {
        clearTimeout(failSafe);
        stage.classList.remove('is-loading');
        stage.classList.add('is-ready');
    }

    function fail(title, detail) {
        finish();
        note(title, detail);
    }

    // ---- Muat pratinjau ----
    var seq = 0;          // nomor permintaan terakhir; jawaban yang sudah usang diabaikan
    var ctrl = null;      // untuk membatalkan permintaan lama kalau pilihan berubah lagi
    var fetching = false; // selama true, event load iframe (about:blank) tidak dianggap "selesai"
    var blobUrl = null;

    function showBlob(blob) {
        var next = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
        var old = blobUrl;
        blobUrl = next;
        clearTimeout(failSafe);
        failSafe = setTimeout(finish, 4000); // PDF-nya sudah ada di sini; jaga-jaga kalau browser tidak mengirim event load untuk PDF
        frame.src = next + VIEW;
        if (old) { setTimeout(function () { URL.revokeObjectURL(old); }, 5000); }
    }

    function explain(err) {
        if (err && err.http) {
            var s = err.http;
            if (s === 401 || s === 419) { return ['Sesi habis', 'Muat ulang halaman ini (Ctrl+F5), lalu masuk lagi kalau diminta.']; }
            if (s === 404) { return ['Pratinjau tidak ditemukan (404)', 'Alamat pratinjau tidak cocok dengan route di server. Coba muat ulang halaman.']; }
            if (s >= 500) { return ['Server gagal membuat PDF (HTTP ' + s + ')', 'Lihat pesan terakhir di storage/logs/laravel.log. Tombol Unduh PDF akan menampilkan galatnya.']; }
            return ['Pratinjau ditolak server (HTTP ' + s + ')', 'Coba muat ulang halaman ini.'];
        }
        if (err && err.notPdf) {
            return ['Server membalas halaman lain, bukan PDF', 'Kalau lewat link tunnel: buka link-nya di tab ini, klik Continue di halaman peringatan, lalu muat ulang. Bisa juga sesi login sudah habis.'];
        }
        if (err && err.timeout) {
            return ['Pembuatan PDF terlalu lama', 'Server mungkin sedang sibuk melayani orang lain. Klik Perbarui untuk mencoba lagi.'];
        }
        return ['Tidak bisa terhubung ke server', 'Cek koneksi atau pastikan php artisan serve masih jalan, lalu klik Perbarui.'];
    }

    function load() {
        updateLinks();
        clearNote();

        // Chrome Android, atau Chrome yang disetel "Download PDF", tidak menampilkan PDF di dalam halaman.
        if (navigator.pdfViewerEnabled === false) {
            fail('Browser ini tidak menampilkan PDF di dalam halaman', 'Pakai tombol Buka dan cetak atau Unduh PDF di bawah. Hasilnya tetap sesuai pilihan kamu.');
            return;
        }

        stage.classList.add('is-loading');
        clearTimeout(failSafe);

        // Tanpa fetch (browser sangat lama): cara lama, langsung ke iframe.
        // "_" membuat alamatnya selalu baru, supaya PDF dibuat ulang walaupun pilihannya sama.
        if (!window.fetch || !window.URL || !URL.createObjectURL) {
            failSafe = setTimeout(finish, 15000);
            frame.src = baseUrl + '?' + params({ _: String(Date.now()) }) + VIEW;
            return;
        }

        if (ctrl) { ctrl.abort(); }
        ctrl = window.AbortController ? new AbortController() : null;
        var mine = ++seq;
        var timedOut = false;
        fetching = true;

        failSafe = setTimeout(function () {
            if (mine !== seq) { return; }
            timedOut = true;
            if (ctrl) { ctrl.abort(); }
        }, TIMEOUT);

        fetch(baseUrl + '?' + params({ _: String(Date.now()) }), {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/pdf', 'X-Tunnel-Skip-AntiPhishing-Page': '1' },
            signal: ctrl ? ctrl.signal : undefined
        }).then(function (res) {
            if (mine !== seq) { return null; }
            if (!res.ok) { throw { http: res.status }; }
            var type = (res.headers.get('Content-Type') || '').toLowerCase();
            if (type.indexOf('pdf') === -1) { throw { notPdf: true }; }
            return res.blob();
        }).then(function (blob) {
            if (mine !== seq || !blob) { return; }
            fetching = false;
            showBlob(blob);
        }).catch(function (err) {
            if (mine !== seq) { return; } // diganti permintaan yang lebih baru, bukan galat
            fetching = false;
            if (timedOut) { err = { timeout: true }; }
            var msg = explain(err);
            fail(msg[0], msg[1]);
        });
    }

    frame.addEventListener('load', function () { if (!fetching) { finish(); } });

    // Tunda sebentar saat banyak perubahan beruntun (mengetik judul, klik beberapa warna) supaya PDF tidak dibuat berkali-kali.
    var timer;
    function later(ms) {
        clearTimeout(timer);
        updateLinks();
        timer = setTimeout(load, ms);
    }

    function onChange(e) {
        if (e.target && FIELDS.indexOf(e.target.name) !== -1) { later(500); }
    }
    form.addEventListener('input', onChange);
    form.addEventListener('change', onChange);

    select.addEventListener('change', function () { later(0); });
    refreshBtn.addEventListener('click', function () { clearTimeout(timer); load(); });

    // Setelah menyimpan, halaman dimuat ulang dan yang tersimpan = yang tampil, jadi tanda "Belum disimpan" hilang sendiri.
    // Pratinjau pertama dimuat setelah halaman selesai tampil, supaya animasi masuk tidak tersendat.
    // Kalau event "load" halaman tak kunjung datang (ada file lain yang macet), tetap mulai setelah 1,5 detik.
    updateLinks();
    var started = false;
    function start() {
        if (started) { return; }
        started = true;
        setTimeout(load, 150);
    }
    if (document.readyState === 'complete') { start(); } else {
        window.addEventListener('load', start);
        setTimeout(start, 1500);
    }
})();
