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
        Schema::create('truks', function (Blueprint $table) {
            $table->uuid('uid_truk')->primary();
            $table->uuid('uid_user');
            $table->string('input_nopol', 30);
            $table->string('qr_code', 255)->nullable();
            $table->decimal('volume', 10, 2);
            $table->string('foto_truk', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truks');
    }
};
