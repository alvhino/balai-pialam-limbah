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
        Schema::create('cek_ph', function (Blueprint $table) {
            $table->uuid('uid_ph')->primary();
            $table->uuid('uid_truk');
            $table->decimal('ph', 5, 2);
            $table->decimal('biaya', 15, 2)->nullable();
            $table->string('foto', 255);
            $table->string('jenis_limbah', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cek_phs');
    }
};
