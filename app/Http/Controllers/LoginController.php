<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $req)
    {
        try {
            $req->validate([
                'no_hp' => 'required|numeric|digits_between:10,18',
                'password' => 'required|string|min:6',
            ]);

            $user = User::where('no_hp', $req->no_hp)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Login gagal! Pengguna tidak ditemukan.'
                ], 404);
            }

            if (!Hash::check($req->password, $user->password)) {
                return response()->json([
                    'message' => 'Login gagal! Password salah.'
                ], 401);
            }

            $token = $user->createToken('MyApp')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil.',
                'data' => $user,
                'token' => $token
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
