<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan tampilan invoice/PDF per akun: judul, logo, info bank, catatan kaki, template, font, warna.
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('invoice_title', 30)->default('INVOICE');
            $table->string('logo_path')->nullable();
            $table->string('bank_name', 60)->nullable();
            $table->string('bank_account', 40)->nullable();
            $table->string('bank_holder', 80)->nullable();
            $table->string('footer_note', 200)->nullable();
            $table->string('pdf_template', 12)->default('modern');
            $table->string('pdf_font', 12)->default('sans');
            $table->string('pdf_color', 12)->default('biru');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_title', 'logo_path', 'bank_name', 'bank_account', 'bank_holder',
                'footer_note', 'pdf_template', 'pdf_font', 'pdf_color',
            ]);
        });
    }
};
