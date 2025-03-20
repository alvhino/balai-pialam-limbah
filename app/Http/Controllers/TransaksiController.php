<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{

    public function index()
    {
        try {
            $result = DB::select('SELECT * FROM get_data_transaksi()');
    
            return response()->json([
                'status' => 200,
                'message' => 'Data transaksi berhasil diambil',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal mengambil data transaksi: ' . $e->getMessage());
    
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat mengambil data transaksi',
                'errors' => ['detail' => $e->getMessage()]
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'uid_truk' => 'required|uuid|exists:truks,uid_truk'
            ]);
    
            $result = DB::select('SELECT * FROM create_transaksi(?)', [$request->uid_truk]);
    
            return response()->json([
                'status' => 201,
                'message' => 'Transaksi berhasil disimpan',
                'data' => $result[0] ?? null
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Query error saat menyimpan transaksi: ' . $e->getMessage());
    
            preg_match('/ERROR:\s+(.*)\n/s', $e->getMessage(), $match);
            $cleanMessage = $match[1] ?? 'Terjadi kesalahan pada database';
    
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat menyimpan transaksi',
                'errors' => [
                    'message' => trim($cleanMessage)
                ]
            ], 500);
        } catch (\Throwable $e) {
            \Log::error('Unexpected error saat menyimpan transaksi: ' . $e->getMessage());
    
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan tidak terduga',
                'errors' => [
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            DB::statement("SELECT hapus_transaksi(?)", [$id]);
    
            return response()->json([
                'status' => 200,
                'message' => 'Data transaksi berhasil dihapus.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal menghapus data transaksi',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    
    public function searchTransaksi(Request $request)
    {
        $keyword = $request->input('keyword');

        if (!$keyword) {
            return response()->json([
                'message' => 'Keyword tidak boleh kosong'
            ], 400);
        }

        $results = DB::select("SELECT * FROM search_transaksi(?)", [$keyword]);

        return response()->json([
            'message' => 'Data ditemukan',
            'data' => $results
        ], 200);
    }

}
