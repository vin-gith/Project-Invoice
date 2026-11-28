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
    // Landing selalu dibuka terang. Pilihan mode baru diingat setelah pengunjung menekan tombolnya.
    document.documentElement.setAttribute('data-theme', 'light');
    try { localStorage.setItem('theme', 'light'); } catch (e) {}})();
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}">
</head>
<body>
    {{-- landing-nav: navbar turun pelan saat halaman dibuka, tidak langsung muncul (animasi navDrop di app.css). --}}
    <header class="topbar landing-nav">
        <a class="brand" href="{{ route('home') }}">Invoicely</a>
        
        {{-- ============ Navbar section ============ --}}
        <nav class="nav-links" aria-label="Bagian halaman">
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
                {{-- intro-*: animasi pembuka (app.css, bagian "Animasi pembuka landing").
                     Judul dipecah per kata (span.w, urutan --w); "tagihan jelas" dibungkus utuh supaya gradasinya tetap. --}}
                <h1 class="intro-title"><span class="w" style="--w:0">Invoice</span> <span class="w" style="--w:1">rapi,</span> <span class="w" style="--w:2"><span class="accent">tagihan jelas</span>,</span> <span class="w" style="--w:3">pembayaran</span> <span class="w" style="--w:4">tercatat.</span></h1>
                <p class="l-lead intro-lead">
                    Invoicely membantu kamu membuat faktur penjualan, menghitung total tagihan secara otomatis,
                    mencatat status lunas, dan mengunduhnya sebagai PDF. Semuanya dari satu tempat.
                </p>
                <ul class="hero-points intro-points">
                    <li style="--p:0">Nomor invoice otomatis</li>
                    <li style="--p:1">Unduh PDF sekali klik</li>
                    <li style="--p:2">Template invoice</li>
                    <li style="--p:3">Cetak invoice otomatis</li>
                    <li style="--p:4">Input logo otomatis</li>
                </ul>
            </div>
        </section>

        {{-- ============ Fitur ============ --}}
        <section class="l-section" id="fitur">
            <div class="l-wrap">
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
            <span class="l-foot-left"><strong>Invoicely</strong> - Sistem invoice digital</span>
            <div class="l-contact">
                <span class="l-contact-title">Hubungi kami</span>
                <a class="l-contact-mail" href="mailto:Contohxxx@gmail.com" translate="no">Contohxxx@gmail.com</a>
            </div>
            <span class="l-foot-right">&copy; {{ date('Y') }}</span>
        </footer>
    </main>

    <script src="{{ asset('js/app.js') }}"></script>
    {{-- GSAP + ScrollTrigger (versi dikunci, integrity = file tidak bisa diubah diam-diam oleh CDN).
         Kalau gagal dimuat, landing tetap jalan dengan animasi CSS biasa. --}}
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.15.1/dist/gsap.min.js" integrity="sha384-3vCAhD+a3BOliP3QqjQINn6ZWiZxoqu0WFxX5ZQZgqekDZuSb1mJBrrLMmu94H8f" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.15.1/dist/ScrollTrigger.min.js" integrity="sha384-H2WL1iy/jKQQ7MTHU2hskZKNHTYWWD/KgKE1vCNnaWY8An2ESTh23KN/fTp8XS3j" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.3.27/dist/lenis.min.js" integrity="sha384-R18XzqnxvVLEF+BYfhpmDx30v7eP83fAckyfoRzK7/Uf6nGqufcmsk0eAKo7S3RU" crossorigin="anonymous"></script>
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
            var lenis = null;   // diisi motion() kalau scroll halus Lenis aktif
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
                    // Dengan Lenis, Lenis yang menggulung (dan otomatis berhenti kalau pengguna menggulung sendiri).
                    if (lenis) { lenis.scrollTo(end, { duration: dur / 1000, easing: ease }); return; }
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

            // Motion GSAP + ScrollTrigger (blok "MOTION GSAP" di app.css). Kalau GSAP gagal dimuat atau
            // "kurangi animasi" aktif, bagian ini dilewati dan landing memakai animasi muncul .sr di bawah.
            var motionOn = !reduce && !!window.gsap && !!window.ScrollTrigger;
            if (motionOn) { motion(); }

            function motion() {
                gsap.registerPlugin(ScrollTrigger);
                document.documentElement.classList.add('gsap-on');

                // Scroll halus Lenis, digerakkan oleh jam GSAP supaya ScrollTrigger membaca posisi yang sama tiap frame.
                // Hanya roda mouse/touchpad; geser jari di HP tetap scroll bawaan. Kalau Lenis gagal dimuat, scroll biasa.
                if (window.Lenis) {
                    lenis = new Lenis();
                    lenis.on('scroll', ScrollTrigger.update);
                    gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
                    gsap.ticker.lagSmoothing(0);
                }

                var ease = 'power3.out';

                // Hero: isi mundur, mengecil, dan memudar saat di-scroll. scrub = ikut scroll dengan sedikit jeda.
                gsap.to('.hero-center', {
                    yPercent: -12, scale: 0.94, opacity: 0, ease: 'none',
                    scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom 15%', scrub: 0.6 }
                });

                // Judul section: dipecah per kata (span.sw), lalu naik bergantian sekali saat masuk layar.
                gsap.utils.toArray('.l-title').forEach(function (h) {
                    var nodes = [], words = [];
                    h.textContent.trim().split(/\s+/).forEach(function (word, k) {
                        var s = document.createElement('span');
                        s.className = 'sw';
                        s.textContent = word;
                        if (k) { nodes.push(' '); }
                        nodes.push(s);
                        words.push(s);
                    });
                    h.classList.remove('sr');
                    h.replaceChildren.apply(h, nodes);
                    gsap.from(words, {
                        yPercent: 70, opacity: 0, duration: 0.8, ease: ease, stagger: 0.06,
                        scrollTrigger: { trigger: h, start: 'top 85%', once: true }
                    });
                });

                // Paragraf dan kartu: naik bergantian per kelompok yang masuk layar bersamaan.
                // transition dimatikan selama animasi (bentrok dengan GSAP), lalu dibersihkan supaya hover kartu jalan lagi.
                var items = gsap.utils.toArray('.sr');
                items.forEach(function (el) { el.classList.remove('sr'); });
                gsap.set(items, { y: 56, scale: 0.96, opacity: 0, transition: 'none' });
                ScrollTrigger.batch(items, {
                    start: 'top 88%',
                    once: true,
                    onEnter: function (batch) {
                        gsap.to(batch, {
                            y: 0, scale: 1, opacity: 1, duration: 0.9, ease: ease, stagger: 0.12,
                            clearProps: 'transform,opacity,transition'
                        });
                    }
                });

                // Cara kerja: nomor langkah membesar, lalu garis ke langkah berikutnya terisi, mengikuti scroll.
                // Selesai saat bagian atasnya di 55% layar, jadi saat menu "Cara kerja" diklik semuanya sudah lengkap.
                var steps = gsap.utils.toArray('.step-l');
                if (steps.length) {
                    gsap.set(steps, { '--fill': 0 });
                    var tl = gsap.timeline({
                        scrollTrigger: { trigger: '.steps-l', start: 'top 90%', end: 'top 55%', scrub: 0.6 }
                    });
                    steps.forEach(function (s, i) {
                        tl.from(s.querySelector('.num'), { scale: 0.4, opacity: 0.3, ease: 'back.out(2)', duration: 0.4 });
                        if (i < steps.length - 1) { tl.to(s, { '--fill': 1, ease: 'none', duration: 0.6 }); }
                    });
                }

                // Huruf web selesai dimuat bisa menggeser tinggi halaman: hitung ulang posisi pemicu.
                if (document.fonts) { document.fonts.ready.then(function () { ScrollTrigger.refresh(); }); }
            }

            // Elemen .sr dan .steps-l muncul (class "in") saat masuk layar.
            var targets = document.querySelectorAll('.sr, .steps-l');
            if (motionOn) {
                // Sudah diatur GSAP.
            } else if (reduce || !('IntersectionObserver' in window)) {
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
