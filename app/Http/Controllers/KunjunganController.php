<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Kunjungan;

class KunjunganController extends Controller
{
    public function store(Request $request)
{
    try {
        $request->validate([
            'uid_truk' => 'required|uuid|exists:truks,uid_truk',
        ]);

        $now = now('Asia/Jakarta')->format('H:i:s');

        $kunjungan = new Kunjungan;
        $kunjungan->uid_kunjungan = Str::uuid();
        $kunjungan->uid_truk = $request->uid_truk;
        $kunjungan->tanggal = now('Asia/Jakarta')->toDateString();
        $kunjungan->status = 'masuk';
        $kunjungan->jam_kunjungan = [
            'masuk' => $now
        ];        
        $kunjungan->save();

        return response()->json([
            'status' => 201,
            'message' => 'Kunjungan berhasil disimpan',
            'data' => $kunjungan
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Terjadi kesalahan saat menyimpan data kunjungan.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
