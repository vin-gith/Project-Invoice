<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Logo penagih disimpan per invoice (salinan file sendiri), jadi mengganti logo di
        // "Data usaha" nanti tidak mengubah invoice yang sudah dibuat.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('seller_logo_path')->nullable()->after('seller_phone');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('seller_logo_path');
        });
    }
};
