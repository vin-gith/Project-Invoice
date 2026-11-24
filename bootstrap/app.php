<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai header dari tunnel (serveo / ngrok). Tanpa ini Laravel mengira halaman
        // diakses lewat http, lalu link CSS dan JS dibuat http dan diblokir browser (mixed content).
        // Hanya dari 127.0.0.1 / ::1: agen tunnel jalan di komputer ini dan meneruskan ke localhost.
        // Jangan pakai '*': siapa pun bisa memalsukan header X-Forwarded-For, sehingga IP palsu itu
        // dipakai sebagai kunci batas login (5 kali salah) dan throttle, dan batasnya bisa dijebol.
        // Kalau nanti dipasang di belakang proxy lain (Nginx, Cloudflare, load balancer), tambahkan IP proxy itu di sini.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // Pengguna yang SUDAH login lalu membuka /login atau /register (route grup "guest") dulu dilempar ke "/"
        // (halaman pembuka), jadi tombol Masuk terasa tidak berfungsi. Sekarang langsung ke daftar invoice.
        $middleware->redirectUsersTo(fn () => route('invoices.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
