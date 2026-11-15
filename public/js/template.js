/* Halaman "Template invoice": pratinjau PDF langsung.
   Setiap pilihan di form (template, font, warna, judul, bank, catatan kaki) atau pergantian invoice
   dikirim sebagai query string ke route pratinjau, lalu PDF-nya ditampilkan di iframe.
   Tidak ada yang disimpan di sini; menyimpan tetap lewat tombol "Simpan template". */
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

    var failSafe;
    function finish() {
        clearTimeout(failSafe);
        stage.classList.remove('is-loading');
        stage.classList.add('is-ready');
    }

    function load() {
        updateLinks();
        stage.classList.add('is-loading');
        clearTimeout(failSafe);
        failSafe = setTimeout(finish, 15000); // jaga-jaga kalau browser tidak mengirim event load untuk PDF
        // "_" membuat alamatnya selalu baru, supaya PDF dibuat ulang walaupun pilihannya sama.
        // Bagian setelah "#" hanya untuk penampil PDF Chrome (tanpa toolbar, lebar pas).
        frame.src = baseUrl + '?' + params({ _: String(Date.now()) }) + '#toolbar=0&navpanes=0&view=FitH';
    }

    frame.addEventListener('load', finish);

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
    updateLinks();
    if (document.readyState === 'complete') { setTimeout(load, 150); } else { window.addEventListener('load', function () { setTimeout(load, 150); }); }
})();
