<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data penagih disimpan di tiap invoice, jadi invoice lama tidak ikut berubah
        // kalau data usaha diganti. Kolom nullable: invoice lama (null) memakai data usaha.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('seller_name', 120)->nullable()->after('number');
            $table->string('seller_address', 255)->nullable()->after('seller_name');
            $table->string('seller_email', 120)->nullable()->after('seller_address');
            $table->string('seller_phone', 30)->nullable()->after('seller_email');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['seller_name', 'seller_address', 'seller_email', 'seller_phone']);
        });
    }
};
