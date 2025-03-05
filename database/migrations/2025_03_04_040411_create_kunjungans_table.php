<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->uuid('uid_kunjungan')->primary();
            $table->uuid('uid_truk');
            $table->date('tanggal');
            $table->enum('status', ['masuk', 'cek_ph','cek_volume','bayar','keluar']);
            $table->json('jam_kunjungan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};
