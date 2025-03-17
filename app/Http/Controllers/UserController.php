<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function index()
{
    try {
        $users = DB::select('SELECT * FROM get_data_users()');

        $data = collect($users)->map(function ($user) {
            return [
                'uid_user'       => $user->uid_user,
                'nama'           => $user->nama,
                'no_hp'          => $user->no_hp,
                'role'           => $user->role,
                'foto_ktp'       => asset('storage/' . $user->foto_ktp),
                'tgl_registrasi' => \Carbon\Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'status'  => 200,
            'message' => 'Berhasil menampilkan semua user',
            'data'    => $data,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 500,
            'message' => 'Gagal menampilkan user',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function Show($uid_user)
{
    try {
        $user = DB::select('SELECT * FROM get_detail_user(?)', [$uid_user]);

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $u = $user[0];
        return response()->json([
            'status' => 200,
            'message' => 'User ditemukan',
            'data' => [
                'uid_user'       => $u->uid_user,
                'nama'           => $u->nama,
                'no_hp'          => $u->no_hp,
                'role'           => $u->role,
                'foto_ktp'       => asset('storage/' . $u->foto_ktp),
                'tgl_registrasi' => \Carbon\Carbon::parse($u->created_at)->format('Y-m-d H:i:s'),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 500,
            'message' => 'Gagal mengambil data user',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function store(Request $request)
{
    try {
        $request->validate([
            'nama' => 'required|string|max:100|unique:users,nama',
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

        $uid = Str::uuid();
        $fotoKtpPath = $request->file('foto_ktp')->store('ktp', 'public');
        $hashedPassword = bcrypt($request->password);

        DB::statement('SELECT create_user(?, ?, ?, ?, ?, ?)', [
            $uid,
            $request->nama,
            $request->no_hp,
            $hashedPassword,
            $fotoKtpPath,
            $request->role,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'User berhasil didaftarkan',
            'data' => [
                'uid_user' => $uid,
                'nama' => $request->nama,
                'no_hp' => $request->no_hp,
                'role' => $request->role,
                'foto_ktp' => asset('storage/' . $fotoKtpPath),
            ]
        ], 201);
    } catch (\Exception $e) {
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
        $nama = $request->input('nama_user');
        $noHp = $request->input('no_hp');

        $results = DB::select('SELECT * FROM search_user(:nama, :no_hp)', [
            'nama' => $nama,
            'no_hp' => $noHp,
        ]);

        if (empty($results)) {
            return response()->json([
                'status' => 404,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Hasil pencarian user',
            'data' => collect($results)->map(function ($user) {
                return [
                    'uid_user' => $user->uid_user,
                    'nama' => $user->nama,
                    'no_hp' => $user->no_hp,
                    'role' => $user->role,
                    'foto_ktp' => asset('storage/' . $user->foto_ktp),
                    'tgl_registrasi' => \Carbon\Carbon::parse($user->created_at)->format('Y-m-d'),
                ];
            }),
        ]);
    } catch (\Exception $e) {
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
            'nama' => 'nullable|string|max:100|unique:users,nama,' . $uid_user . ',uid_user',
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
        $result = DB::selectOne('SELECT hapus_user(?) AS response', [$uid_user]);
        $response = json_decode($result->response, true);

        if ($response['status'] === 404) {
            return response()->json($response, 404);
        }

        if (!empty($response['foto_ktp']) && Storage::disk('public')->exists($response['foto_ktp'])) {
            Storage::disk('public')->delete($response['foto_ktp']);
        }

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
