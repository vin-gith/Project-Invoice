<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoicely - Sistem Invoice Digital</title>
    <meta name="description" content="Buat faktur penjualan, hitung total tagihan otomatis, catat status pembayaran, dan unduh invoice dalam bentuk PDF.">
    {{-- Dipasang sebelum CSS dimuat: tema tersimpan langsung dipakai, dan class "js" memberi tahu CSS
         bahwa animasi scroll boleh dipakai (tanpa JavaScript, semua konten tetap tampil). --}}
    <script>
        (function () {
            document.documentElement.classList.add('js');
            try {
                var t = localStorage.getItem('theme');
                if (!t) { t = 'light'; } // pertama kali dibuka: selalu terang. Pilihan pengguna tetap diingat.
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">Invoicely</a>

        <nav class="nav-links" aria-label="Bagian halaman">
            <a href="#contoh">Contoh invoice</a>
            <a href="#fitur">Fitur</a>
            <a href="#cara-kerja">Cara kerja</a>
            <a href="#mulai" class="nav-cta">Daftar / Masuk</a>
        </nav>

        <button type="button" class="theme-switch" id="themeSwitch" role="switch" aria-checked="false" aria-label="Ganti mode tampilan">
            <span class="theme-label" id="themeLabel"><span class="tl-dark">Gelap</span><span class="tl-light">Terang</span></span>
            <span class="theme-track"><span class="theme-knob"></span></span>
        </button>
    </header>

    <main class="landing page">

        {{-- ============ Hero ============ --}}
        <section class="hero">
            <div class="blob blob-a"></div>
            <div class="blob blob-b"></div>

            <div class="l-wrap hero-center">
                <p class="l-eyebrow reveal" style="--i:0">Sistem invoice digital</p>
                <h1 class="reveal" style="--i:1">Invoice rapi, <span class="accent">tagihan jelas</span>, pembayaran tercatat.</h1>
                <p class="l-lead reveal" style="--i:2">
                    Invoicely membantu kamu membuat faktur penjualan, menghitung total tagihan secara otomatis,
                    mencatat status lunas, dan mengunduhnya sebagai PDF. Semuanya dari satu tempat.
                </p>
                <ul class="hero-points reveal" style="--i:4">
                    <li>Nomor invoice otomatis</li>
                    <li>Unduh PDF sekali klik</li>
                    <li>Mode gelap dan terang</li>
                </ul>
            </div>

            {{-- Contoh daftar invoice (hiasan, data fiktif). Badge pada baris pertama berganti sendiri. --}}
            <div class="l-wrap reveal" style="--i:5" id="contoh" aria-hidden="true">
                <div class="card demo">
                    <div class="demo-bar">
                        <div class="demo-left">
                            <span class="btn btn-sm btn-primary">Invoice baru</span>
                            <strong>Daftar invoice</strong>
                        </div>
                        <span class="demo-search">Cari nomor atau pelanggan...</span>
                        <span class="demo-count">1-8 / 8</span>
                    </div>
                    <div class="demo-scroll">
                        <table class="demo-table">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Pelanggan</th>
                                    <th class="hide-s">Tgl invoice</th>
                                    <th class="hide-s">Jatuh tempo</th>
                                    <th class="r">Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="row-reveal" style="--i:6"><td class="no">INV-10-11-2026-0004</td><td>Toko Maju Jaya</td><td class="hide-s">10/11/2026</td><td class="hide-s due-now">Hari ini</td><td class="r">Rp 954.600</td><td><span class="badge badge-unpaid" id="mockBadge">Belum lunas</span></td></tr>
                                <tr class="row-reveal" style="--i:7"><td class="no">INV-10-11-2026-0003</td><td>CV Berkah Sentosa</td><td class="hide-s">10/11/2026</td><td class="hide-s">24/11/2026</td><td class="r">Rp 3.330.000</td><td><span class="badge badge-paid">Lunas</span></td></tr>
                                <tr class="row-reveal" style="--i:8"><td class="no">INV-09-11-2026-0002</td><td>Apotek Sehat Sejahtera</td><td class="hide-s">09/11/2026</td><td class="hide-s">16/11/2026</td><td class="r">Rp 1.275.000</td><td><span class="badge badge-unpaid">Belum lunas</span></td></tr>
                                <tr class="row-reveal" style="--i:9"><td class="no">INV-09-11-2026-0001</td><td>PT Karya Mandiri</td><td class="hide-s">09/11/2026</td><td class="hide-s">23/11/2026</td><td class="r">Rp 12.450.000</td><td><span class="badge badge-paid">Lunas</span></td></tr>
                                <tr class="row-reveal" style="--i:10"><td class="no">INV-02-11-2026-0001</td><td>Warung Bu Rina</td><td class="hide-s">02/11/2026</td><td class="hide-s">16/11/2026</td><td class="r">Rp 486.500</td><td><span class="badge badge-unpaid">Belum lunas</span></td></tr>
                                <tr class="row-reveal" style="--i:11"><td class="no">INV-28-10-2026-0002</td><td>Bengkel Jaya Motor</td><td class="hide-s">28/10/2026</td><td class="hide-s due-late">9 hari lalu</td><td class="r">Rp 2.180.000</td><td><span class="badge badge-overdue">Terlambat</span></td></tr>
                                <tr class="row-reveal" style="--i:12"><td class="no">INV-20-10-2026-0001</td><td>Toko Bangunan Abadi</td><td class="hide-s">20/10/2026</td><td class="hide-s due-late">17 hari lalu</td><td class="r">Rp 7.620.000</td><td><span class="badge badge-overdue">Terlambat</span></td></tr>
                                <tr class="row-reveal" style="--i:13"><td class="no">INV-05-10-2026-0001</td><td>UD Sumber Rejeki</td><td class="hide-s">05/10/2026</td><td class="hide-s">19/10/2026</td><td class="r">Rp 915.000</td><td><span class="badge badge-paid">Lunas</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Fitur ============ --}}
        <section class="l-section" id="fitur">
            <div class="l-wrap">
                <p class="l-eyebrow sr">Fitur</p>
                <h2 class="l-title sr" style="--i:1">Semua yang dibutuhkan untuk menagih pelanggan.</h2>
                <p class="l-lead sr" style="--i:2">Tidak perlu lagi menghitung manual atau membuat faktur dari awal setiap kali.</p>

                <div class="features">
                    <article class="card feature sr" style="--i:0">
                        <span class="feature-no">01</span>
                        <h3>Invoice otomatis</h3>
                        <p>Isi data pelanggan dan item tagihan, sisanya diurus sistem. Bisa diubah atau dihapus kapan saja.</p>
                    </article>
                    <article class="card feature sr" style="--i:1">
                        <span class="feature-no">02</span>
                        <h3>Nomor invoice otomatis</h3>
                        <p>Nomor dibuat berurutan dengan format tanggal, bulan, dan tahun, serta dijaga agar tidak pernah kembar.</p>
                    </article>
                    <article class="card feature sr" style="--i:2">
                        <span class="feature-no">03</span>
                        <h3>Total dihitung otomatis</h3>
                        <p>Subtotal, pajak, dan total tagihan terhitung langsung saat kamu mengetik, lalu dihitung ulang di server.</p>
                    </article>
                    <article class="card feature sr" style="--i:0">
                        <span class="feature-no">04</span>
                        <h3>Status pembayaran</h3>
                        <p>Tandai lunas atau belum lunas dengan satu klik. Invoice yang lewat jatuh tempo otomatis berstatus terlambat.</p>
                    </article>
                    <article class="card feature sr" style="--i:1">
                        <span class="feature-no">05</span>
                        <h3>Unduh PDF</h3>
                        <p>Setiap invoice bisa diunduh sebagai file PDF yang rapi, siap dikirim ke pelanggan.</p>
                    </article>
                    <article class="card feature sr" style="--i:2">
                        <span class="feature-no">06</span>
                        <h3>Desain invoice sendiri</h3>
                        <p>Pilih template, font, dan warna, pasang logo usaha di kop dan watermark, lalu unduh atau cetak langsung.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- ============ Cara kerja ============ --}}
        <section class="l-section l-alt" id="cara-kerja">
            <div class="l-wrap">
                <p class="l-eyebrow sr">Cara kerja</p>
                <h2 class="l-title sr" style="--i:1">Tiga langkah dari kosong sampai tertagih.</h2>

                <div class="steps-l">
                    <div class="step-l sr" style="--i:0">
                        <span class="num">1</span>
                        <h3>Daftar dan masuk</h3>
                        <p>Buat akun dalam hitungan detik. Login dilindungi batas percobaan supaya akunmu tetap aman.</p>
                    </div>
                    <div class="step-l sr" style="--i:1">
                        <span class="num">2</span>
                        <h3>Isi invoice</h3>
                        <p>Masukkan pelanggan, tanggal, item, dan pajak. Total langsung terhitung di layar.</p>
                    </div>
                    <div class="step-l sr" style="--i:2">
                        <span class="num">3</span>
                        <h3>Kirim dan catat</h3>
                        <p>Unduh PDF dan kirim ke pelanggan, lalu tandai lunas begitu pembayaran diterima.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Daftar dan masuk ============ --}}
        <section class="l-section" id="mulai">
            <div class="l-wrap">
                <p class="l-eyebrow sr">Mulai</p>
                <h2 class="l-title sr" style="--i:1">Siap membuat invoice pertamamu?</h2>
                <p class="l-lead sr" style="--i:2">Gratis dicoba. Daftar, isi data pelanggan, dan invoice pertamamu jadi dalam hitungan menit.</p>

                <div class="join-grid">
                    <article class="card join sr" style="--i:0">
                        <h3>Belum punya akun?</h3>
                        <p>Buat akun dalam hitungan detik, lalu langsung mulai membuat invoice.</p>
                        <a class="btn btn-lg btn-primary" href="{{ route('register') }}">Daftar akun</a>
                    </article>
                    <article class="card join sr" style="--i:1">
                        <h3>Sudah punya akun?</h3>
                        <p>Masuk untuk melihat daftar invoice dan melanjutkan pekerjaanmu.</p>
                        <a class="btn btn-lg btn-ghost" href="{{ route('login') }}">Masuk</a>
                    </article>
                </div>
            </div>
        </section>

        <footer class="l-wrap l-footer">
            <span><strong>Invoicely</strong> - Sistem invoice digital</span>
            <span>&copy; {{ date('Y') }}</span>
        </footer>
    </main>

    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        (function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Setiap halaman ini dibuka atau di-refresh, mulai dari paling atas.
            // Alamat tidak diberi "#fitur" lagi saat menu diklik, dan kalau masih ada, dibersihkan.
            if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
            if (location.hash) { history.replaceState(null, '', location.pathname + location.search); }
            window.scrollTo({ top: 0, behavior: 'instant' });

            // Scroll halus saat klik menu (Contoh invoice, Fitur, Cara kerja): mulai pelan, melaju, lalu berhenti pelan.
            // Kalau pengguna menggulung sendiri di tengah jalan, animasi langsung dihentikan.
            function ease(t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; }
            document.querySelectorAll('a[href^="#"]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    var el = document.querySelector(a.getAttribute('href'));
                    if (!el || reduce) { return; }
                    e.preventDefault();
                    var start = window.pageYOffset;
                    var margin = parseFloat(getComputedStyle(el).scrollMarginTop) || 0;
                    var end = Math.max(0, el.getBoundingClientRect().top + start - margin);
                    var dist = end - start;
                    var dur = Math.min(1400, Math.max(700, Math.abs(dist) * 0.9));
                    var t0 = null, stop = false;
                    function cancel() { stop = true; }
                    window.addEventListener('wheel', cancel, { once: true, passive: true });
                    window.addEventListener('touchstart', cancel, { once: true, passive: true });
                    function step(now) {
                        if (stop) { return; }
                        if (t0 === null) { t0 = now; }
                        var p = Math.min(1, (now - t0) / dur);
                        window.scrollTo({ top: start + dist * ease(p), behavior: 'instant' });
                        if (p < 1) { requestAnimationFrame(step); }
                    }
                    requestAnimationFrame(step);
                });
            });

            // Elemen .sr dan .steps-l muncul (class "in") saat masuk layar.
            var targets = document.querySelectorAll('.sr, .steps-l');
            if (reduce || !('IntersectionObserver' in window)) {
                targets.forEach(function (el) { el.classList.add('in'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('in');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });
                targets.forEach(function (el) { io.observe(el); });
            }

            // Badge pada contoh invoice berganti: Belum lunas -> Lunas -> Belum lunas ...
            var badge = document.getElementById('mockBadge');
            if (badge && !reduce) {
                var paid = false;
                setInterval(function () {
                    paid = !paid;
                    badge.textContent = paid ? 'Lunas' : 'Belum lunas';
                    badge.className = 'badge ' + (paid ? 'badge-paid' : 'badge-unpaid');
                    // Ulang animasi "pop" supaya perubahan terasa.
                    badge.style.animation = 'none';
                    void badge.offsetWidth;
                    badge.style.animation = '';
                }, 3200);
            }
        })();
    </script>
</body>
</html>
