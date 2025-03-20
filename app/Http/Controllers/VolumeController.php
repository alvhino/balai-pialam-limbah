<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VolumeController extends Controller
{
    
    public function index()
{
    try {
        $data = DB::select('SELECT * FROM get_data_volume()');

        $data = collect($data)->map(function ($item) {
            $item->foto = asset('storage/' . $item->foto);
            return $item;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Data volume berhasil diambil',
            'data' => $data
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Gagal mengambil data volume',
            'error' => $e->getMessage()
        ]);
    }
}

public function store(Request $request)
{
    try {
        $request->validate([
            'uid_truk' => 'required|uuid',
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'total_volume' => 'required|numeric',
        ]);

        $trukExists = DB::table('truks')->where('uid_truk', $request->uid_truk)->exists();

        if (!$trukExists) {
            return response()->json([
                'status' => 404,
                'message' => 'Truk dengan UID tersebut tidak ditemukan.'
            ], 404);
        }

        $fotoPath = $request->file('foto')->store('foto_volume', 'public');
        $fotoUrl = asset('storage/' . $fotoPath);

        DB::statement("SELECT create_volume(?, ?, ?)", [
            $request->uid_truk,
            $fotoPath,
            $request->total_volume
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Data volume berhasil disimpan.',
            'data' => [
                'uid_truk' => $request->uid_truk,
                'total_volume' => $request->total_volume,
                'foto' => $fotoUrl
            ]
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 422,
            'message' => 'Validasi gagal',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();

        if (str_contains($errorMessage, 'Truk belum melakukan kunjungan hari ini')) {
            return response()->json([
                'status' => 422,
                'message' => 'Truk belum melakukan kunjungan hari ini.'
            ], 422);
        } elseif (str_contains($errorMessage, 'Truk belum melakukan cek pH hari ini')) {
            return response()->json([
                'status' => 422,
                'message' => 'Truk belum melakukan cek pH hari ini.'
            ], 422);
        } elseif (str_contains($errorMessage, 'Volume sudah dicatat')) {
            return response()->json([
                'status' => 409,
                'message' => 'Volume sudah dicatat untuk truk ini hari ini.'
            ], 409);
        }

        return response()->json([
            'status' => 500,
            'message' => 'Terjadi kesalahan saat menyimpan data.',
            'error' => $errorMessage,
        ], 500);
    }
}

public function destroy($id)
{
    try {
        DB::statement("SELECT hapus_volume(?)", [$id]);

        return response()->json([
            'status' => 200,
            'message' => 'Data volume berhasil dihapus.'
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => 500,
            'message' => 'Gagal menghapus data volume',
            'error' => $th->getMessage()
        ], 500);
    }
}

public function searchVolume(Request $request)
{
    try {
        $tanggal = $request->query('tanggal');
        $nopol = $request->query('nopol');
        $nama_supir = $request->query('nama_supir');

        if (!empty($tanggal) && !strtotime($tanggal)) {
            $tanggal = null;
        }

        $result = DB::select('SELECT * FROM search_volume(?, ?, ?)', [
            $tanggal,
            $nopol,
            $nama_supir
        ]);

        foreach ($result as &$item) {
            $item->foto = url('storage/' . $item->foto);
        }

        if (empty($result)) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Data ditemukan',
            'data' => $result
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
