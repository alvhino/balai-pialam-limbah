<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Truk;
use App\Models\Kunjungan;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KunjunganController extends Controller
{
    
    public function index(Request $request)
{
    try {
        $namaSupir = $request->nama_supir;
        $tanggal = $request->tanggal;
        $nopol = $request->nopol;

        $results = DB::select("SELECT * FROM get_kunjungan_data(?, ?, ?)", [
            $request->nama_supir,
            $request->tanggal,
            $request->nopol
        ]);
        
        if (empty($results)) {
            return response()->json([
                'status' => 404,
                'message' => 'Data kunjungan tidak ditemukan',
                'data' => [],
                'jumlah_truk_di_dalam' => 0
            ], 404);
        }

        $jumlahBelumKeluar = DB::table('kunjungans')
            ->where('status', '!=', 'keluar')
            ->count();

            return response()->json([
                'status' => 200,
                'message' => 'Berhasil menampilkan data kunjungan',
                'data' => $results,
                'jumlah_truk_di_dalam' => Kunjungan::where('status', '!=', 'keluar')->count()
            ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Gagal menampilkan data kunjungan',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function store(Request $request)
{
    try {
        $request->validate([
            'uid_truk' => 'required|uuid|exists:truks,uid_truk',
        ]);

        $result = DB::selectOne("SELECT create_kunjungan(?) AS result", [$request->uid_truk]);
        $response = json_decode($result->result, true);

        return response()->json($response, $response['status']);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 422,
            'message' => 'Validasi gagal.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Terjadi kesalahan saat menyimpan data kunjungan.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
