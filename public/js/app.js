/* ==========================================================================
   Invoicely - interaksi: tema, menu geser, transisi halaman, konfirmasi,
   angka berhitung, dan form invoice bertahap. Tanpa library tambahan.
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var html = document.documentElement;
    var body = document.body;

    function rupiah(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));
    }

    /* ---------------------------------------------------------------
       Tema terang / gelap
       Pilihan disimpan di localStorage. Skrip kecil di <head> layout
       sudah memasang tema sebelum halaman tampil, jadi tidak berkedip.
       --------------------------------------------------------------- */
    var themeSwitch = document.getElementById('themeSwitch');
    var themeLabel = document.getElementById('themeLabel');

    function paintThemeSwitch() {
        var dark = html.getAttribute('data-theme') === 'dark';
        themeSwitch.setAttribute('aria-checked', dark ? 'true' : 'false');
        themeLabel.textContent = dark ? 'Gelap' : 'Terang';
    }

    if (themeSwitch) {
        paintThemeSwitch();
        themeSwitch.addEventListener('click', function () {
            var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.classList.add('theme-anim');
            html.setAttribute('data-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
            paintThemeSwitch();
            setTimeout(function () { html.classList.remove('theme-anim'); }, 450);
        });
    }

    /* ---------------------------------------------------------------
       Menu geser (drawer)
       --------------------------------------------------------------- */
    var menuBtn = document.getElementById('menuBtn');
    var drawer = document.getElementById('drawer');
    var scrim = document.getElementById('scrim');

    function setDrawer(open) {
        body.classList.toggle('drawer-open', open);
        body.classList.toggle('no-scroll', open);
        menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuBtn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        drawer.inert = !open;
        if (open) {
            var first = drawer.querySelector('a');
            if (first) { setTimeout(function () { first.focus({ preventScroll: true }); }, 250); }
        }
    }

    if (menuBtn && drawer) {
        menuBtn.addEventListener('click', function () {
            setDrawer(!body.classList.contains('drawer-open'));
        });
        scrim.addEventListener('click', function () { setDrawer(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && body.classList.contains('drawer-open')) {
                setDrawer(false);
                menuBtn.focus();
            }
        });
    }

    /* ---------------------------------------------------------------
       Transisi antar halaman
       Saat link internal diklik, halaman saat ini geser keluar dulu
       (class .leaving), baru pindah. Halaman baru masuk dengan animasi
       .page di CSS.
       --------------------------------------------------------------- */
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (!a) { return; }
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        if (a.target && a.target !== '_self') { return; }
        if (a.hasAttribute('download') || a.hasAttribute('data-no-transition')) { return; }

        var url;
        try { url = new URL(a.href, window.location.href); } catch (err) { return; }
        if (url.origin !== window.location.origin) { return; }
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) { return; }

        e.preventDefault();
        if (reduceMotion) { window.location.href = a.href; return; }

        body.classList.add('leaving');
        setTimeout(function () { window.location.href = a.href; }, 220);
    });

    // Tombol "back" browser bisa memulihkan halaman dari cache dalam keadaan .leaving.
    window.addEventListener('pageshow', function () {
        body.classList.remove('leaving');
    });

    /* ---------------------------------------------------------------
       Notifikasi (toast) hilang sendiri
       --------------------------------------------------------------- */
    var toast = document.getElementById('toast');
    if (toast) {
        var hideToast = function () {
            toast.classList.add('is-leaving');
            setTimeout(function () { toast.remove(); }, 400);
        };
        var toastTimer = setTimeout(hideToast, 4200);
        toast.addEventListener('click', function () { clearTimeout(toastTimer); hideToast(); });
    }

    /* ---------------------------------------------------------------
       Angka yang berhitung naik (data-count)
       --------------------------------------------------------------- */
    function countUp(el) {
        var target = Number(el.getAttribute('data-count'));
        if (isNaN(target)) { return; }
        var asRupiah = el.getAttribute('data-format') === 'rupiah';
        var format = function (n) { return asRupiah ? rupiah(n) : String(Math.round(n)); };

        if (reduceMotion || target === 0) { el.textContent = format(target); return; }

        var duration = 900;
        var start = null;
        function frame(ts) {
            if (start === null) { start = ts; }
            var t = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - t, 3);
            el.textContent = format(target * eased);
            if (t < 1) { requestAnimationFrame(frame); }
        }
        el.textContent = format(0);
        requestAnimationFrame(frame);
    }
    document.querySelectorAll('[data-count]').forEach(countUp);

    /* ---------------------------------------------------------------
       Konfirmasi lewat modal (form dengan data-confirm)
       --------------------------------------------------------------- */
    var modal = document.getElementById('modal');
    var modalText = document.getElementById('modalText');
    var modalOk = document.getElementById('modalOk');
    var pendingForm = null;
    var lastFocus = null;

    function openModal(text, form) {
        pendingForm = form;
        lastFocus = document.activeElement;
        modalText.textContent = text;
        modal.hidden = false;
        // Dua frame supaya transisi CSS jalan dari keadaan awal.
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { modal.classList.add('open'); });
        });
        setTimeout(function () { modalOk.focus(); }, 60);
    }

    function closeModal() {
        modal.classList.remove('open');
        setTimeout(function () { modal.hidden = true; }, 320);
        pendingForm = null;
        if (lastFocus) { lastFocus.focus(); }
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target.hasAttribute('data-close')) { closeModal(); }
        });
        modalOk.addEventListener('click', function () {
            var form = pendingForm;
            modal.classList.remove('open');
            if (form) { form.submit(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) { closeModal(); }
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        var message = form.getAttribute && form.getAttribute('data-confirm');
        if (message && modal) {
            e.preventDefault();
            openModal(message, form);
        }
    });

    /* ---------------------------------------------------------------
       Data usaha: pratinjau logo yang baru dipilih (sebelum disimpan).
       --------------------------------------------------------------- */
    (function () {
        var fileInput = document.getElementById('logo');
        var box = document.getElementById('logoPreview');
        if (!fileInput || !box || !window.URL) { return; }

        fileInput.addEventListener('change', function () {
            var f = fileInput.files && fileInput.files[0];
            if (!f) { return; }
            if (f.size > 1024 * 1024) {
                alert('Ukuran logo maksimal 1 MB. Pilih file yang lebih kecil.');
                fileInput.value = '';
                return;
            }
            var img = document.createElement('img');
            img.alt = 'Pratinjau logo';
            img.src = URL.createObjectURL(f);
            box.innerHTML = '';
            box.appendChild(img);
        });
    })();

    /* ---------------------------------------------------------------
       Pencarian invoice langsung (halaman daftar invoice).
       - Hasil tabel ikut berubah saat mengetik (tanpa menekan Cari).
       - Kolom dikosongkan/dihapus -> semua invoice muncul lagi.
       - Di bawah kolom muncul rekomendasi nama pelanggan / nomor invoice yang cocok.
       Tanpa JavaScript, form tetap jalan seperti biasa (tekan Cari).
       --------------------------------------------------------------- */
    (function () {
        var formEl = document.querySelector('[data-live-search]');
        if (!formEl || !window.fetch || !window.URL) { return; }

        var input = formEl.querySelector('input[name="q"]');
        var statusInput = formEl.querySelector('input[name="status"]');
        var results = document.getElementById('invoiceResults');
        var list = document.getElementById('suggestList');
        if (!input || !results || !list) { return; }

        var loadTimer = null, sugTimer = null, loadCtl = null, sugCtl = null, active = -1, lastTerm = input.value.trim();

        function urlFor(q) {
            var u = new URL(formEl.getAttribute('action'), window.location.origin);
            if (q) { u.searchParams.set('q', q); }
            if (statusInput && statusInput.value) { u.searchParams.set('status', statusInput.value); }
            return u;
        }

        // Ambil ulang daftar dari server lalu tukar isi tabel + tombol filter.
        function load(q) {
            var u = urlFor(q);
            if (loadCtl) { loadCtl.abort(); }
            loadCtl = new AbortController();
            var ctl = loadCtl;
            results.classList.add('is-loading');

            fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', signal: ctl.signal })
                .then(function (r) { if (!r.ok) { throw new Error('http ' + r.status); } return r.text(); })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var fresh = doc.getElementById('invoiceResults');
                    if (!fresh) { throw new Error('format'); }
                    results.innerHTML = fresh.innerHTML;
                    var pills = document.querySelector('.pills'), freshPills = doc.querySelector('.pills');
                    if (pills && freshPills) { pills.innerHTML = freshPills.innerHTML; }
                    history.replaceState(null, '', u.pathname + u.search);
                    results.classList.remove('is-loading');
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') { return; }
                    results.classList.remove('is-loading');
                    formEl.submit(); // cadangan: kalau gagal, pakai cara biasa
                });
        }

        function closeList() {
            list.hidden = true;
            list.innerHTML = '';
            active = -1;
            input.setAttribute('aria-expanded', 'false');
        }

        function choose(value) {
            input.value = value;
            lastTerm = value.trim();
            closeList();
            load(lastTerm);
        }

        function renderList(items) {
            list.innerHTML = '';
            active = -1;
            if (!items.length) { closeList(); return; }
            items.forEach(function (it) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                var btn = document.createElement('button');
                btn.type = 'button';
                var main = document.createElement('span');
                main.textContent = it.value;
                var hint = document.createElement('small');
                hint.textContent = it.hint;
                btn.appendChild(main);
                btn.appendChild(hint);
                // mousedown (bukan click) supaya terjadi sebelum kolom kehilangan fokus
                btn.addEventListener('mousedown', function (e) { e.preventDefault(); choose(it.value); });
                li.appendChild(btn);
                list.appendChild(li);
            });
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function suggest(q) {
            if (sugCtl) { sugCtl.abort(); }
            if (!q) { closeList(); return; }
            sugCtl = new AbortController();
            var u = new URL(formEl.getAttribute('data-suggest-url'), window.location.origin);
            u.searchParams.set('q', q);
            fetch(u.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', signal: sugCtl.signal })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (items) {
                    if (input.value.trim() === q && document.activeElement === input) { renderList(items); }
                })
                .catch(function () {});
        }

        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(loadTimer);
            clearTimeout(sugTimer);
            if (q === lastTerm) { return; }
            lastTerm = q;
            // Kolom dikosongkan: langsung tampilkan semua invoice lagi.
            if (!q) { closeList(); load(''); return; }
            loadTimer = setTimeout(function () { load(q); }, 280);
            sugTimer = setTimeout(function () { suggest(q); }, 160);
        });

        // Tekan Enter / tombol Cari: tanpa pindah halaman.
        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(loadTimer);
            lastTerm = input.value.trim();
            closeList();
            load(lastTerm);
        });

        input.addEventListener('keydown', function (e) {
            var opts = list.querySelectorAll('li');
            if (list.hidden || !opts.length) { return; }
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                active = (active + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length;
                opts.forEach(function (li, i) { li.classList.toggle('is-active', i === active); });
            } else if (e.key === 'Enter' && active > -1) {
                e.preventDefault();
                choose(opts[active].querySelector('span').textContent);
            } else if (e.key === 'Escape') {
                closeList();
            }
        });

        input.addEventListener('focus', function () { if (input.value.trim()) { suggest(input.value.trim()); } });
        input.addEventListener('blur', function () { setTimeout(closeList, 120); });
    })();

    /* ---------------------------------------------------------------
       Kolom telepon: awalan "+62" tampil tetap (bukan isi kolom), jadi tidak bisa dihapus.
       Kolom hanya menerima angka dan harus diawali 8; kalau menempel nomor lengkap (+62 8.., 08.., 628..) awalan +62 / 0 dibuang.
       --------------------------------------------------------------- */
    document.querySelectorAll('[data-phone]').forEach(function (wrap) {
        var input = wrap.querySelector('input');

        // Klik di awalan (+62) tetap mengaktifkan kolom.
        wrap.addEventListener('mousedown', function (e) {
            if (e.target !== input) { e.preventDefault(); input.focus(); }
        });

        // Kolom ini sengaja hanya menerima angka. Supaya tidak terasa "tidak bisa mengetik",
        // keterangan di bawahnya berubah merah sebentar saat ada ketikan yang ditolak.
        var hint = wrap.nextElementSibling;
        var hintText = hint ? hint.textContent : '';
        var hintTimer = null;
        function warn(message) {
            wrap.classList.remove('nudge');
            void wrap.offsetWidth;
            wrap.classList.add('nudge');
            if (!hint) { return; }
            hint.textContent = message;
            hint.classList.add('is-warn');
            clearTimeout(hintTimer);
            hintTimer = setTimeout(function () {
                hint.textContent = hintText;
                hint.classList.remove('is-warn');
            }, 2200);
        }

        input.addEventListener('beforeinput', function (e) {
            if (!e.data || String(e.inputType).indexOf('insert') !== 0) { return; }
            if (/\D/.test(e.data)) {
                e.preventDefault();
                warn('Hanya boleh angka (tanpa spasi, huruf, atau tanda baca).');
                return;
            }
            // Hasil akhir setelah ketikan ini (memperhitungkan teks yang sedang diblok).
            var next = input.value.slice(0, input.selectionStart) + e.data + input.value.slice(input.selectionEnd);
            if (next.charAt(0) !== '8') {
                e.preventDefault();
                warn('Setelah +62 langsung angka 8, tanpa 0 di depan.');
            } else if (next.length > input.maxLength) {
                e.preventDefault();
                warn('Maksimal ' + input.maxLength + ' angka setelah +62.');
            }
        });

        input.addEventListener('input', function () {
            var clean = input.value.replace(/\D/g, '');
            if (clean !== input.value) { input.value = clean; }
        });

        input.addEventListener('paste', function (e) {
            var text = (e.clipboardData || window.clipboardData).getData('text');
            var d = text.replace(/\D/g, '');
            if (d.indexOf('62') === 0) { d = d.slice(2); }
            else if (d.charAt(0) === '0') { d = d.slice(1); }
            e.preventDefault();
            input.value = d.slice(0, input.maxLength);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            if (input.value && input.value.charAt(0) !== '8') {
                warn('Nomor seluler Indonesia diawali angka 8 setelah +62.');
            }
        });
    });

    /* ---------------------------------------------------------------
       Tombol "Tampilkan / Sembunyikan" di kolom password.
       Cukup bungkus input dengan .pw-wrap dan beri tombol [data-pw-toggle];
       satu listener di document melayani semua kolom password.
       --------------------------------------------------------------- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pw-toggle]');
        if (!btn) { return; }

        var input = btn.closest('.pw-wrap').querySelector('input');
        var show = input.type === 'password';

        // Mengganti type password <-> text sudah cukup; nilai, batas panjang, dan
        // aturan validasi pada input tetap sama.
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Sembunyikan' : 'Tampilkan';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        input.focus();
    });

    /* ---------------------------------------------------------------
       Form invoice: dua langkah yang bergeser, baris item dinamis,
       dan total yang dihitung langsung.
       --------------------------------------------------------------- */
    var form = document.getElementById('invoiceForm');
    if (!form) { return; }

    // Form ini punya kolom unggah logo, jadi harus dikirim sebagai multipart supaya filenya ikut terkirim.
    form.setAttribute('enctype', 'multipart/form-data');

    var track = document.getElementById('slidesTrack');
    var slides = Array.prototype.slice.call(form.querySelectorAll('.slide'));
    var steps = Array.prototype.slice.call(form.querySelectorAll('.step'));
    var fill = document.getElementById('stepsFill');
    var prevBtn = document.getElementById('prevBtn');
    var nextBtn = document.getElementById('nextBtn');
    var saveBtn = document.getElementById('saveBtn');
    var cancelBtn = document.getElementById('cancelBtn');
    var list = document.getElementById('items');
    var tpl = document.getElementById('itemTpl');
    var addBtn = document.getElementById('addItem');
    var taxInput = form.querySelector('[name="tax_percent"]');
    var issueInput = document.getElementById('issue_date');
    var dueInput = document.getElementById('due_date');

    var last = slides.length - 1;
    var step = Math.min(Math.max(parseInt(form.getAttribute('data-start-step') || '0', 10), 0), last);
    var rowCounter = 1000; // indeks baru tidak akan bentrok dengan indeks bawaan server

    function goTo(n, focus) {
        step = n;
        track.style.transform = 'translateX(-' + (n * 100) + '%)';
        fill.style.width = (((n + 1) / slides.length) * 100) + '%';

        slides.forEach(function (s, i) {
            s.inert = i !== n;                 // field di slide tersembunyi tidak bisa difokus lewat Tab
            s.setAttribute('aria-hidden', i !== n ? 'true' : 'false');
            s.classList.toggle('is-current', i === n);
        });
        steps.forEach(function (s, i) {
            s.classList.toggle('is-active', i === n);
            s.classList.toggle('is-done', i < n);
        });

        prevBtn.hidden = n === 0;
        cancelBtn.hidden = n !== 0;
        nextBtn.hidden = n === last;
        saveBtn.hidden = n !== last;

        if (focus) {
            setTimeout(function () {
                var f = slides[n].querySelector('input, textarea');
                if (f) { f.focus({ preventScroll: true }); }
            }, reduceMotion ? 0 : 450);
        }
    }

    // Jatuh tempo tidak boleh sebelum tanggal terbit (server juga memeriksa ini).
    function checkDates() {
        var bad = issueInput.value && dueInput.value && dueInput.value < issueInput.value;
        dueInput.setCustomValidity(bad ? 'Jatuh tempo tidak boleh sebelum tanggal terbit.' : '');
    }
    issueInput.addEventListener('input', checkDates);
    dueInput.addEventListener('input', checkDates);

    function fieldsOf(i) { return slides[i].querySelectorAll('input, textarea, select'); }

    function stepIsValid(i) {
        checkDates();
        var ok = true;
        fieldsOf(i).forEach(function (f) { if (!f.checkValidity()) { ok = false; } });
        return ok;
    }

    function reportStep(i) {
        checkDates();
        var fields = fieldsOf(i);
        for (var k = 0; k < fields.length; k++) {
            if (!fields[k].checkValidity()) { fields[k].reportValidity(); return false; }
        }
        return true;
    }

    prevBtn.addEventListener('click', function () { goTo(step - 1, true); });
    nextBtn.addEventListener('click', function () {
        if (reportStep(step)) { goTo(step + 1, true); }
    });

    // Enter di langkah 1 = lanjut, bukan kirim form.
    form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && step < last && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            nextBtn.click();
        }
    });

    form.addEventListener('submit', function (e) {
        for (var i = 0; i <= last; i++) {
            if (!stepIsValid(i)) {
                e.preventDefault();
                goTo(i, false);
                setTimeout(function (n) { reportStep(n); }.bind(null, i), reduceMotion ? 0 : 520);
                return;
            }
        }
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';
    });

    /* ----- Total langsung ----- */
    var sumEls = {
        sub: form.querySelector('[data-sum="sub"]'),
        tax: form.querySelector('[data-sum="tax"]'),
        total: form.querySelector('[data-sum="total"]')
    };

    function tween(el, to) {
        var from = Number(el._value || 0);
        el._value = to;
        if (el._raf) { cancelAnimationFrame(el._raf); }
        if (reduceMotion || from === to) { el.textContent = rupiah(to); return; }

        var start = null;
        function frame(ts) {
            if (start === null) { start = ts; }
            var t = Math.min((ts - start) / 350, 1);
            var eased = 1 - Math.pow(1 - t, 3);
            el.textContent = rupiah(from + (to - from) * eased);
            if (t < 1) { el._raf = requestAnimationFrame(frame); }
        }
        el._raf = requestAnimationFrame(frame);
    }

    function activeRows() {
        return Array.prototype.slice.call(list.querySelectorAll('[data-row]:not(.row-out)'));
    }

    function recalc() {
        var sub = 0;
        activeRows().forEach(function (row) {
            var qty = Math.max(0, parseInt(row.querySelector('[data-qty]').value, 10) || 0);
            var price = Math.max(0, parseInt(row.querySelector('[data-price]').value, 10) || 0);
            var line = qty * price;
            sub += line;
            row.querySelector('[data-line]').textContent = rupiah(line);
        });

        var pct = parseFloat(taxInput.value) || 0;
        var tax = Math.round(sub * pct / 100);   // sama dengan round() di server
        tween(sumEls.sub, sub);
        tween(sumEls.tax, tax);
        tween(sumEls.total, sub + tax);
    }

    // Minimal satu baris item harus tersisa.
    function updateRemoveState() {
        var rows = activeRows();
        rows.forEach(function (row) {
            row.querySelector('[data-remove]').disabled = rows.length <= 1;
        });
    }

    list.addEventListener('input', recalc);
    taxInput.addEventListener('input', recalc);

    addBtn.addEventListener('click', function () {
        list.insertAdjacentHTML('beforeend', tpl.innerHTML.split('__INDEX__').join(String(rowCounter++)));
        var row = list.lastElementChild;
        if (!reduceMotion) { row.classList.add('row-in'); }
        updateRemoveState();
        recalc();
        var desc = row.querySelector('input');
        if (desc) { desc.focus({ preventScroll: true }); }
    });

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-remove]');
        if (!btn || btn.disabled) { return; }
        var row = btn.closest('[data-row]');

        var finish = function () { row.remove(); updateRemoveState(); recalc(); };
        if (reduceMotion) { finish(); return; }

        row.classList.add('row-out');
        updateRemoveState();
        recalc();
        row.addEventListener('animationend', finish, { once: true });
    });

    // Keadaan awal.
    goTo(step, false);
    updateRemoveState();
    recalc();
})();
