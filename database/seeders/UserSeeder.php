<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'uid_user' => Str::uuid(),
            'nama' => 'Admin',
            'no_hp' => '081234567891',
            'password' => Hash::make('admin123'),
            'foto_ktp' => 'http://localhost:8000/storage/foto_ktp/dummy.jpg',
            'role' => 'admin',
        ]);
    }
}
