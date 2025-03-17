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
                'message' => 'Data PH',
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
                'message' => 'Truk belum terdaftar di kunjungan hari ini',
            ], 400);
        }

        $fotoPath = $request->file('foto')->store('foto_ph', 'public');
        $fotoUrl = asset('storage/' . $fotoPath);

        $result = DB::selectOne("SELECT simpan_ph(?, ?, ?, ?, ?) AS message", [
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
        return response()->json([
            'status' => 500,
            'message' => 'Gagal menyimpan data PH',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
