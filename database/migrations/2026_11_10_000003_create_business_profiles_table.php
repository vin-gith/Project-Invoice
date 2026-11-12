<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data usaha (bagian "Dari" di invoice dan PDF). Hanya satu baris.
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('address', 255);
            $table->string('email', 120);
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
