<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini biasanya sudah dibuat migrasi bawaan Laravel (users). Dibuat di sini hanya kalau belum ada,
        // karena fitur "Lupa password" menyimpan token reset di situ.
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Sengaja kosong: tabel ini milik migrasi bawaan Laravel, jangan dihapus dari sini.
    }
};
