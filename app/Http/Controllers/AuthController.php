<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Batas percobaan login. Ubah angka di sini kalau mau diganti.
    private const MAX_ATTEMPTS = 5;     // salah berapa kali sebelum dikunci
    private const LOCK_SECONDS = 60;    // lama dikunci (detik)
    private const WINDOW_SECONDS = 600; // kegagalan dihitung dalam rentang ini (10 menit)

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            // Batas 72 hanya pengaman supaya kolom login tidak bisa diisi teks sepanjang apa pun.
            // Sengaja bukan 8 agar akun lama yang passwordnya lebih panjang tetap bisa masuk.
            'password' => ['required', 'string', 'max:72'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'max' => ':attribute terlalu panjang.',
        ], [
            'email' => 'Email',
            'password' => 'Password',
        ]);

        // Kunci pembatas dibuat dari email + IP: kegagalan di satu akun tidak mengunci
        // akun orang lain, dan satu IP tidak bisa menebak password tanpa batas.
        $failKey = $this->throttleKey($request);
        $lockKey = $failKey . '|lock';

        // Sedang dalam masa kunci? Tolak langsung tanpa memeriksa password sama sekali.
        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            return $this->lockedResponse($lockKey);
        }

        // Auth::attempt mencari user berdasarkan email, lalu membandingkan password
        // yang diketik dengan hash di database. Argumen kedua = fitur "ingat saya".
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Catat satu kegagalan. Hitungan ini hangus sendiri setelah WINDOW_SECONDS.
            $failures = RateLimiter::hit($failKey, self::WINDOW_SECONDS);

            if ($failures >= self::MAX_ATTEMPTS) {
                // Kegagalan terakhir: reset hitungan, lalu mulai masa kunci.
                RateLimiter::clear($failKey);
                RateLimiter::hit($lockKey, self::LOCK_SECONDS);

                return $this->lockedResponse($lockKey);
            }

            $left = self::MAX_ATTEMPTS - $failures;

            // Pesan sengaja satu untuk dua kemungkinan (email salah atau password salah),
            // supaya orang luar tidak bisa menebak email mana yang terdaftar.
            return back()
                ->withErrors(['email' => "Email atau password salah. Sisa percobaan: {$left}."])
                ->onlyInput('email');
        }

        // Login berhasil: hapus catatan kegagalan.
        RateLimiter::clear($failKey);

        // Ganti ID sesi setelah login supaya ID lama yang mungkin sudah dicuri tidak berguna
        // (mencegah session fixation).
        $request->session()->regenerate();

        // intended() = kembali ke halaman yang tadi dituju sebelum dilempar ke login.
        return redirect()->intended(route('invoices.index'));
    }

    /** Kunci untuk menghitung percobaan: email (huruf kecil) + alamat IP. */
    private function throttleKey(Request $request): string
    {
        return 'login|' . Str::transliterate(Str::lower((string) $request->input('email')) . '|' . $request->ip());
    }

    /**
     * Kembali ke form login dengan pesan terkunci. lock_seconds dipakai halaman login
     * untuk menampilkan hitungan mundur di tombol Masuk.
     */
    private function lockedResponse(string $lockKey): RedirectResponse
    {
        return back()
            ->withErrors(['email' => 'Salah 5 kali. Login dikunci sementara, tunggu hitungan mundur selesai.'])
            ->with('lock_seconds', RateLimiter::availableIn($lockKey))
            ->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        // 'rfc' memeriksa bentuk alamat email. 'dns' memeriksa domainnya benar-benar ada
        // (punya record DNS/MX), jadi "nama@gmail.con" atau domain karangan ditolak.
        // Pemeriksaan DNS butuh internet. Untuk mematikannya (misalnya demo tanpa internet),
        // tambahkan baris 'check_email_dns' => false, di config/invoicely.php.
        $emailRule = config('invoicely.check_email_dns', true) ? 'email:rfc,dns' : 'email:rfc';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:users,name'],
            'email' => ['required', $emailRule, 'max:120', 'unique:users,email'],
            // Minimal 8 karakter dan wajib mengandung huruf, angka, dan simbol.
            // Regex memakai tiga lookahead: masing-masing memastikan ada minimal satu
            // huruf, satu angka, dan satu simbol (bukan huruf/angka/spasi) di mana pun posisinya.
            // Panjang dikunci tepat 8 karakter (min:8 dan max:8). Kalau suatu saat mau
            // dilonggarkan, ubah angka max di sini (batas teknis bcrypt: 72 karakter),
            // lalu samakan maxlength di register.blade.php.
            'password' => [
                'required', 'string', 'min:8', 'max:8', 'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d\s])/',
            ],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Email tidak valid atau domainnya tidak ditemukan.',
            'unique' => ':attribute sudah terdaftar.',
            'name.unique' => 'Nama ini sudah dipakai akun lain. Pilih nama yang berbeda.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute terlalu panjang.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'password.max' => 'Password maksimal :max karakter.',
            'password.regex' => 'Password harus mengandung huruf, angka, dan simbol (contoh: ! @ # ?).',
        ], [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            // Password disimpan sebagai hash bcrypt, bukan teks asli.
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Akun berhasil dibuat. Selamat datang, ' . $user->name . '.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Hapus seluruh isi sesi dan buat token CSRF baru.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah keluar.');
    }
}
