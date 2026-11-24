<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pemeriksaan DNS email butuh internet; test harus bisa jalan offline.
        config(['invoicely.check_email_dns' => false]);
    }

    public function test_register_accepts_password_longer_than_8_characters(): void
    {
        $this->post('/register', [
            'name' => 'Toko Budi',
            'email' => 'budi@example.com',
            'password' => 'Rahasia#Panjang123',
            'password_confirmation' => 'Rahasia#Panjang123',
        ])->assertRedirect(route('invoices.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_register_rejects_password_shorter_than_8_characters(): void
    {
        $this->post('/register', [
            'name' => 'Toko Budi',
            'email' => 'budi@example.com',
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_register_rejects_password_without_symbol(): void
    {
        $this->post('/register', [
            'name' => 'Toko Budi',
            'email' => 'budi@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_login_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'Benar#123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Benar#123'])
            ->assertRedirect(route('invoices.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_locked_after_5_wrong_passwords(): void
    {
        $user = User::factory()->create(['password' => 'Benar#123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'Salah#123']);
        }

        // Masih dalam masa kunci: password benar pun ditolak.
        $this->post('/login', ['email' => $user->email, 'password' => 'Benar#123'])
            ->assertSessionHas('lock_seconds');

        $this->assertGuest();
    }

    public function test_spoofed_forwarded_for_header_does_not_bypass_login_lock(): void
    {
        $user = User::factory()->create(['password' => 'Benar#123']);

        // Permintaan langsung dari internet (bukan dari tunnel di localhost), dengan IP palsu
        // yang berganti tiap percobaan lewat header X-Forwarded-For.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7']);

        for ($i = 1; $i <= 5; $i++) {
            $this->withHeader('X-Forwarded-For', "198.51.100.$i")
                ->post('/login', ['email' => $user->email, 'password' => 'Salah#123']);
        }

        $this->withHeader('X-Forwarded-For', '198.51.100.99')
            ->post('/login', ['email' => $user->email, 'password' => 'Benar#123'])
            ->assertSessionHas('lock_seconds');

        $this->assertGuest();
    }

    public function test_invoice_pages_require_login(): void
    {
        $this->get('/invoices')->assertRedirect(route('login'));
    }
}
