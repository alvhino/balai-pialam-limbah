<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Kunjungan;

class KunjunganController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $query = Kunjungan::with(['truk.user']);
    
            if ($request->has('nama_supir')) {
                $query->whereHas('truk.user', function ($q) use ($request) {
                    $q->where('nama', 'ILIKE', '%' . $request->nama_supir . '%');
                });
            }
    
            if ($request->has('tanggal')) {
                $query->whereDate('tanggal', $request->tanggal);
            }
    
            if ($request->has('nopol')) {
                $query->whereHas('truk', function ($q) use ($request) {
                    $q->where('input_nopol', 'ILIKE', '%' . $request->nopol . '%');
                });
            }
    
            $kunjungans = $query->get()->map(function ($kunjungan) {
                $jam = $kunjungan->jam_kunjungan;
                $jamMasuk = $jam['masuk'] ?? null;
                $jamKeluar = $jam['keluar'] ?? null;
                $durasi = '-';
    
                if ($jamMasuk && $jamKeluar) {
                    $masuk = \Carbon\Carbon::createFromFormat('H:i:s', $jamMasuk);
                    $keluar = \Carbon\Carbon::createFromFormat('H:i:s', $jamKeluar);
                    $durasi = $masuk->diffInMinutes($keluar) . ' menit';
                }
    
                return [
                    'nopol' => $kunjungan->truk->input_nopol ?? '-',
                    'nama_supir' => $kunjungan->truk->user->nama ?? '-',
                    'tanggal' => $kunjungan->tanggal,
                    'status' => $kunjungan->status,
                    'jam_masuk' => $jamMasuk ?? '-',
                    'jam_keluar' => $jamKeluar ?? '-',
                    'durasi' => $durasi,
                ];
            });
    
            $jumlahBelumKeluar = Kunjungan::where('status', '!=', 'keluar')->count();
    
            if ($kunjungans->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Data kunjungan tidak ditemukan',
                    'data' => [],
                    'jumlah_truk_di_dalam' => 0
                ], 404);
            }
    
            return response()->json([
                'status' => 200,
                'message' => 'Berhasil menampilkan data kunjungan',
                'data' => $kunjungans,
                'jumlah_truk_di_dalam' => $jumlahBelumKeluar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal menampilkan data kunjungan',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    

    public function store(Request $request)
    {
        try {
            $request->validate([
                'uid_truk' => 'required|uuid|exists:truks,uid_truk',
            ]);
    
            $today = now('Asia/Jakarta')->toDateString();
    
            $sudahAda = Kunjungan::where('uid_truk', $request->uid_truk)
                ->whereDate('tanggal', $today)
                ->where('status', '!=', 'keluar')
                ->exists();
    
            if ($sudahAda) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Truk ini sudah tercatat masuk hari ini dan belum keluar.',
                ], 409);
            }
    
            $now = now('Asia/Jakarta')->format('H:i:s');
    
            $kunjungan = new Kunjungan;
            $kunjungan->uid_kunjungan = Str::uuid();
            $kunjungan->uid_truk = $request->uid_truk;
            $kunjungan->tanggal = $today;
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
