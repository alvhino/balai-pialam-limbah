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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('uid_user')->primary();
            $table->string('nama', 100);
            $table->string('no_hp', 18);
            $table->string('password');
            $table->string('foto_ktp');
            $table->enum('role', ['admin', 'supir','petugas','executive']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
