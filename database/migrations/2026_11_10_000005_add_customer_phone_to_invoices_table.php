<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nomor telepon pelanggan (opsional), disimpan dengan format "+62 812-3456-7890".
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('customer_phone', 30)->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
