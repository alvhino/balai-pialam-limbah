<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::all()->map(function ($user) {
                return [
                    'uid_user' => $user->uid_user,
                    'nama' => $user->nama,
                    'no_hp' => $user->no_hp,
                    'role' => $user->role,
                    'foto_ktp' => asset('storage/' . $user->foto_ktp),
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Berhasil menampilkan semua user',
                'data' => $users,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal menampilkan user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($uid_user)
    {
        try {
            $user = User::where('uid_user', $uid_user)->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'User ditemukan',
                'data' => [
                    'uid_user' => $user->uid_user,
                    'nama' => $user->nama,
                    'no_hp' => $user->no_hp,
                    'role' => $user->role,
                    'foto_ktp' => asset('storage/' . $user->foto_ktp),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal mengambil user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:100',
                'no_hp' => 'required|string|max:18|unique:users,no_hp',
                'password' => 'required|string|min:6',
                'foto_ktp' => 'required|file|mimes:jpeg,png,jpg|max:4096',
                'role' => 'required|in:admin,supir,petugas,executive',
            ]);

            $mimeType = $request->file('foto_ktp')->getMimeType();
            $allowedMime = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($mimeType, $allowedMime)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'File KTP tidak valid!',
                ], 422);
            }

            $fotoKtpPath = $request->file('foto_ktp')->store('ktp', 'public');

            $user = User::create([
                'uid_user' => Str::uuid(),
                'nama' => $request->nama,
                'no_hp' => $request->no_hp,
                'password' => bcrypt($request->password),
                'foto_ktp' => $fotoKtpPath,
                'role' => $request->role,
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'User berhasil didaftarkan',
                'data' => [
                    'uid_user' => $user->uid_user,
                    'nama' => $user->nama,
                    'no_hp' => $user->no_hp,
                    'role' => $user->role,
                    'foto_ktp' => asset('storage/' . $user->foto_ktp),
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat proses registrasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = User::query();

            $query->where(function ($q) use ($request) {
                if ($request->filled('nama')) {
                    $q->where('nama', 'ilike', '%' . $request->nama . '%');
                }

                if ($request->filled('no_hp')) {
                    $q->orWhere('no_hp', 'ilike', '%' . $request->no_hp . '%');
                }
            });

            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Hasil pencarian user',
                'data' => $users->map(function ($user) {
                    return [
                        'uid_user' => $user->uid_user,
                        'nama' => $user->nama,
                        'no_hp' => $user->no_hp,
                        'role' => $user->role,
                        'foto_ktp' => asset('storage/' . $user->foto_ktp),
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat pencarian',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $uid_user)
    {
        try {
            $request->validate([
                'nama' => 'nullable|string|max:100',
                'no_hp' => 'nullable|string|max:18|unique:users,no_hp,' . $uid_user . ',uid_user',
                'password' => 'nullable|string|min:6',
                'role' => 'nullable|in:admin,supir,petugas,executive',
                'foto_ktp' => 'nullable|file|mimes:jpeg,png,jpg|max:4096',
            ]);

            $user = User::where('uid_user', $uid_user)->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            if ($request->hasFile('foto_ktp')) {
                $mimeType = $request->file('foto_ktp')->getMimeType();
                $allowedMime = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($mimeType, $allowedMime)) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'File KTP tidak valid!',
                    ], 422);
                }

                if ($user->foto_ktp && Storage::disk('public')->exists($user->foto_ktp)) {
                    Storage::disk('public')->delete($user->foto_ktp);
                }

                $user->foto_ktp = $request->file('foto_ktp')->store('ktp', 'public');
            }

            $user->nama = $request->nama ?? $user->nama;
            $user->no_hp = $request->no_hp ?? $user->no_hp;
            $user->role = $request->role ?? $user->role;
            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            $user->save();

            return response()->json([
                'status' => 200,
                'message' => 'User berhasil diupdate',
                'data' => [
                    'uid_user' => $user->uid_user,
                    'nama' => $user->nama,
                    'no_hp' => $user->no_hp,
                    'role' => $user->role,
                    'foto_ktp' => asset('storage/' . $user->foto_ktp),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($uid_user)
    {
        try {
            $user = User::where('uid_user', $uid_user)->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            if ($user->foto_ktp && Storage::disk('public')->exists($user->foto_ktp)) {
                Storage::disk('public')->delete($user->foto_ktp);
            }

            $user->delete();

            return response()->json([
                'status' => 200,
                'message' => 'User berhasil dihapus',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat menghapus user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
