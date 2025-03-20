<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CekPhController extends Controller
{

    public function index()
    {
        try {
            $result = DB::select("SELECT * FROM get_data_ph()");
    
            $data = collect($result)->map(function ($item) {
                $item->foto = asset('storage/' . $item->foto);
                return $item;
            });
    
            return response()->json([
                'status'  => 200,
                'message' => 'Data PH tersedia',
                'data'    => $data
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Gagal mengambil data PH',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'uid_truk' => 'required|uuid',
                'ph' => 'required|numeric',
                'biaya' => 'nullable|numeric',
                'foto' => 'required|image|mimes:jpg,jpeg,png|max:4096',
                'jenis_limbah' => 'required|string|max:255',
            ]);
    
            $isTrukTerdaftar = DB::table('kunjungans')
                ->where('uid_truk', $request->uid_truk)
                ->whereDate('tanggal', now()->toDateString())
                ->exists();
    
            if (!$isTrukTerdaftar) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Truk belum melakukan kunjungan hari ini, silakan daftar kunjungan terlebih dahulu.'
                ], 400);
            }
    
            $fotoPath = $request->file('foto')->store('foto_ph', 'public');
            $fotoUrl = asset('storage/' . $fotoPath);
    
            $result = DB::selectOne("SELECT create_ph(?, ?, ?, ?, ?) AS message", [
                $request->uid_truk,
                $request->ph,
                $request->biaya,
                $fotoPath,
                $request->jenis_limbah
            ]);
    
            return response()->json([
                'status' => 201,
                'message' => $result->message,
                'data' => [
                    'uid_truk' => $request->uid_truk,
                    'ph' => $request->ph,
                    'biaya' => $request->biaya,
                    'foto' => $fotoUrl,
                    'jenis_limbah' => $request->jenis_limbah,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
    
            if (str_contains($message, 'Truk tidak memiliki kunjungan aktif hari ini')) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Truk belum melakukan kunjungan hari ini, silakan daftar kunjungan terlebih dahulu.'
                ], 400);
            }
    
            if (str_contains($message, 'Truk sudah melakukan cek pH pada kunjungan ini')) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Truk sudah melakukan cek pH pada kunjungan ini.'
                ], 409);
            }
    
            return response()->json([
                'status' => 500,
                'message' => 'Gagal menyimpan data pH, terjadi kesalahan pada server.',
                'error' => $message
            ], 500);
        }
    }
    
    public function destroy($id)
{
    try {
        DB::statement("SELECT hapus_cek_ph(?)", [$id]);

        return response()->json([
            'status' => 200,
            'message' => 'Data cek_ph berhasil dihapus.'
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => 500,
            'message' => 'Gagal menghapus data cek_ph',
            'error' => $th->getMessage()
        ], 500);
    }
}

public function show($id)
{
    try {
        $cekPh = DB::table('cek_ph')->where('uid_ph', $id)->first();

        if (!$cekPh) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $cekPh->foto = url('storage/' . $cekPh->foto);

        return response()->json([
            'message' => 'Data ditemukan',
            'data' => $cekPh
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat mengambil data'
        ], 500);
    }
}


public function search(Request $request)
{
    try {
        $keyword = $request->input('keyword');

        if (!$keyword) {
            return response()->json([
                'message' => 'Keyword is required'
            ], 400);
        }

        $results = DB::select("SELECT * FROM search_cek_ph(?)", [$keyword]);

        if (empty($results)) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $results = collect($results)->map(function ($item) {
            $item->foto = url('storage/' . $item->foto);
            return $item;
        });

        return response()->json([
            'message' => 'Data ditemukan',
            'data' => $results
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat mencari data'
        ], 500);
    }
}


}
