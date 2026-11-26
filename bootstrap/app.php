<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        // 419 = token form sudah basi (halaman dibiarkan terbuka lebih dari 2 jam, atau dibuka lagi lewat tombol Back
        // setelah keluar). Daripada layar "419 PAGE EXPIRED", kembali ke form dengan isian tetap ada (kecuali password)
        // dan token baru, jadi cukup tekan kirim sekali lagi.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->withErrors(['expired' => 'Halaman sudah kedaluwarsa. Silakan kirim ulang.']);
        });
    })->create();
