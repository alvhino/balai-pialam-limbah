<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{

    public function login(Request $req)
    {
        try {
            $req->validate([
                'no_hp' => 'required|numeric|digits_between:10,18',
                'password' => 'required|string|min:6',
            ]);
    
            $key = 'login-attempt:' . $req->ip();
    
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'message' => "Terlalu banyak percobaan login. Coba lagi dalam " . ceil($seconds / 60) . " menit."
                ], 429);
            }
    
            $user = User::where('no_hp', $req->no_hp)->first();
    
            if (!$user || !Hash::check($req->password, $user->password)) {
                RateLimiter::hit($key, 300);
                return response()->json([
                    'message' => 'Login gagal! Periksa kembali nomor HP dan password.'
                ], 401);
            }
    
            RateLimiter::clear($key);
    
            $token = $user->createToken('MyApp')->plainTextToken;
    
            return response()->json([
                'message' => 'Login berhasil.',
                'data' => [
                    'uid_user' => $user->uid_user,
                    'nama' => $user->nama,
                    'no_hp' => $user->no_hp,
                    'foto_ktp' => $user->foto_ktp ? asset('storage/' . $user->foto_ktp) : null,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $token
            ], 200);
    
        } catch (\Throwable $th) {
            return response()->json([
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
    
            if ($user) {
               
                $user->currentAccessToken()->delete();
    
                return response()->json([
                    'message' => 'Logout berhasil.'
                ], 200);
            }
    
            return response()->json([
                'message' => 'Tidak ada user yang login.'
            ], 401);
    
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat logout.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    
}
