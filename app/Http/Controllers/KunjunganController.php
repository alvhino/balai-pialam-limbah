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

        $results = DB::select("SELECT * FROM get_data_kunjungan(?, ?, ?)", [
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
                'message' => 'Data kunjungan tersedia',
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

public function searchKunjungan(Request $request)
{
    try {
        $tanggal = $request->query('tanggal');
        $nopol = $request->query('nopol');
        $nama_supir = $request->query('nama_supir');

        if (!empty($tanggal) && !strtotime($tanggal)) {
            $tanggal = null;
        }

        $result = DB::select('SELECT * FROM search_kunjungan(?, ?, ?)', [
            $tanggal,
            $nopol,
            $nama_supir
        ]);

        if (empty($result)) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Data ditemukan',
            'data' => collect($result)->map(function ($row) {
                $row->jam_kunjungan = json_decode($row->jam_kunjungan);
                return $row;
            })
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan',
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

public function keluar(Request $request)
{
    $request->validate([
        'uid_truk' => 'required|uuid'
    ]);

    try {
        $kunjungan = DB::table('kunjungans')
            ->where('uid_truk', $request->uid_truk)
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', '!=', 'keluar')
            ->first();

        if (!$kunjungan) {
            return response()->json([
                'status' => 400,
                'message' => 'Truk tidak memiliki kunjungan aktif atau sudah keluar.'
            ], 400);
        }

        DB::select("SELECT keluar(?)", [$request->uid_truk]);

        return response()->json([
            'status' => 200,
            'message' => 'Berhasil keluar'
        ]);
    } catch (\Throwable $e) {
        $message = $e->getMessage();

        if (str_contains($message, 'Status tidak valid untuk keluar')) {
            return response()->json([
                'status' => 409,
                'message' => 'Status tidak valid untuk keluar, truk sudah keluar sebelumnya.'
            ], 409);
        }

        if (str_contains($message, 'status terakhir adalah cek_volume')) {
            return response()->json([
                'status' => 409,
                'message' => 'Tidak dapat keluar, selesaikan dulu pembayaran Anda.'
            ], 409);
        }

        return response()->json([
            'status' => 500,
            'message' => 'Gagal keluar',
            'error' => $message
        ], 500);
    }
}

public function destroy($id)
{
    try {
        DB::statement("SELECT hapus_kunjungan(?)", [$id]);

        return response()->json([
            'status' => 200,
            'message' => 'Data kunjungan berhasil dihapus.'
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => 500,
            'message' => 'Gagal menghapus kunjungan',
            'error' => $th->getMessage()
        ], 500);
    }
}

}
