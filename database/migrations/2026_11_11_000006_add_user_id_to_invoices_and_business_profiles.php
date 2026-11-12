<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 akun = 1 daftar invoice (dan 1 data usaha). Tiap baris sekarang punya pemilik.
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Data yang sudah ada sebelum fitur ini diserahkan ke akun yang paling pertama dibuat.
        $firstUser = DB::table('users')->min('id');
        if ($firstUser) {
            DB::table('invoices')->whereNull('user_id')->update(['user_id' => $firstUser]);
            DB::table('business_profiles')->whereNull('user_id')->update(['user_id' => $firstUser]);
        }

        // Nomor invoice hanya perlu unik di dalam satu akun, jadi tiap akun mulai dari 0001 sendiri.
        // Indeks baru dibuat dulu sebelum yang lama dibuang (MySQL butuh indeks untuk foreign key).
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['user_id', 'number']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['number']);
        });
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('number');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'number']);
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
