<?php

namespace App\Http\Controllers;

use App\Models\Truk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class TrukController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $searchNopol = $request->query('nopol');
            $searchNama  = $request->query('nama_user');
    
            $truks = Truk::with('user')
                ->when($searchNopol, function ($query, $searchNopol) {
                    $query->where('input_nopol', 'like', '%' . $searchNopol . '%');
                })
                ->when($searchNama, function ($query, $searchNama) {
                    $query->whereHas('user', function ($q) use ($searchNama) {
                        $q->where('nama', 'like', '%' . $searchNama . '%');
                    });
                })
                ->get();
    
            if ($truks->isEmpty()) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Data truk tidak ditemukan',
                    'data'    => []
                ], 404);
            }
    
            $data = $truks->map(function ($truk, $index) {
                return [
                    'no'          => $index + 1,
                    'input_nopol' => $truk->input_nopol,
                    'volume'      => $truk->volume,
                    'nama_user'   => $truk->user->nama ?? 'Tidak diketahui',
                    'foto_truk'   => $truk->foto_truk,
                    'qr_code'     => $truk->qr_code,
                ];
            });
    
            return response()->json([
                'status'  => 200,
                'message' => 'Data truk tersedia',
                'data'    => $data
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Terjadi kesalahan saat mengambil data truk'
            ], 500);
        }
    }
    
    public function Show($uid_truk)
    {
        try {
            $truk = Truk::where('uid_truk', $uid_truk)
                        ->with('user') 
                        ->first();
    
            if (!$truk) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Data truk tidak ditemukan'
                ], 404);
            }
    
            return response()->json([
                'status'  => 200,
                'message' => 'Data truk berhasil ditemukan',
                'data'    => $truk
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            \Log::error('Gagal mengambil data truk: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'message' => 'Terjadi kesalahan saat mengambil data truk'
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama_user'   => 'required|string',
                'input_nopol' => 'required|string|max:30',
                'volume'      => 'required|numeric',
                'foto_truk'   => 'required|image|max:2048',
            ]);
    
            $user = User::where('nama', $request->nama_user)->first();
            if (!$user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }
    
            $fotoPath = $request->file('foto_truk')->store('foto_truk', 'public');
            $fotoUrl  = url('storage/' . $fotoPath);
    
            $uid_truk = Str::uuid()->toString();
    
            $qrData = [
                'uid_truk'   => $uid_truk,
                'nama_supir' => $user->nama,
                'no_hp'      => $user->no_hp,
                'nopol'      => $request->input_nopol,
                'volume'     => $request->volume,
                'foto_truk'  => $fotoUrl
            ];
    
            $dataJson = json_encode($qrData, JSON_UNESCAPED_SLASHES);
    
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($dataJson)
                ->size(300)
                ->margin(10)
                ->build();
    
            $qrFilename = $uid_truk . '.png';
            $qrPath = 'qr_code/' . $qrFilename;
    
            Storage::disk('public')->put($qrPath, $qrResult->getString());
            $qrUrl = url('storage/' . $qrPath);
    
            $truk = Truk::create([
                'uid_truk'    => $uid_truk,
                'uid_user'    => $user->uid_user,
                'input_nopol' => $request->input_nopol,
                'volume'      => $request->volume,
                'foto_truk'   => $fotoUrl,
                'qr_code'     => $qrUrl,
            ]);
    
            return response()->json([
                'status'      => 201,
                'message'     => 'Data Truk berhasil disimpan',
                'download_qr' => route('download.qr', ['filename' => $qrFilename]),
                'data'        => $truk
            ], 201, [], JSON_UNESCAPED_SLASHES);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Terjadi kesalahan saat menyimpan data truk'
            ], 500);
        }
    }

    public function update(Request $request, $uid_truk)
    {
        try {
            $request->validate([
                'nama_user'   => 'required|string',
                'input_nopol' => 'required|string|max:30',
                'volume'      => 'required|numeric',
                'foto_truk'   => 'required|image|max:2048',
            ]);
    
            $truk = Truk::where('uid_truk', $uid_truk)->first();
            if (!$truk) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Data truk tidak ditemukan'
                ], 404);
            }
    
            $user = User::where('nama', $request->nama_user)->first();
            if (!$user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }
    
            $oldPath = str_replace(url('storage') . '/', '', $truk->foto_truk);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
    
            $fotoPath = $request->file('foto_truk')->store('foto_truk', 'public');
            $fotoUrl = url('storage/' . $fotoPath);
    
            $qrData = [
                'uid_truk'   => $truk->uid_truk,
                'nama_supir' => $user->nama,
                'no_hp'      => $user->no_hp,
                'nopol'      => $request->input_nopol,
                'volume'     => $request->volume,
                'foto_truk'  => $fotoUrl
            ];
    
            $dataJson = json_encode($qrData, JSON_UNESCAPED_SLASHES);
    
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($dataJson)
                ->size(300)
                ->margin(10)
                ->build();
    
            $qrFilename = $truk->uid_truk . '.png';
            $qrPath = 'qr_code/' . $qrFilename;
    
            Storage::disk('public')->put($qrPath, $qrResult->getString());
            $qrUrl = url('storage/' . $qrPath);
    
            $truk->update([
                'uid_user'    => $user->uid_user,
                'input_nopol' => $request->input_nopol,
                'volume'      => $request->volume,
                'foto_truk'   => $fotoUrl,
                'qr_code'     => $qrUrl,
            ]);
    
            return response()->json([
                'status'      => 200,
                'message'     => 'Data Truk berhasil diperbarui',
                'download_qr' => route('download.qr', ['filename' => $qrFilename]),
                'data'        => $truk
            ], 200, [], JSON_UNESCAPED_SLASHES);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Gagal update truk: ' . $e->getMessage());
            return response()->json([
                'status'  => 500,
                'message' => 'Terjadi kesalahan saat mengupdate data truk'
            ], 500);
        }
    }
    
    public function downloadQR($filename)
    {
        $path = storage_path("app/public/qr_code/{$filename}");
        if (!file_exists($path)) {
            abort(404, 'QR Code tidak ditemukan');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'image/png',
        ]);
    }

    public function destroy($id)
    {
        try {
            $truk = Truk::where('uid_truk', $id)->first();
    
            if (!$truk) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Data truk tidak ditemukan'
                ], 404);
            }
    
            if ($truk->foto_truk) {
                $fotoPath = str_replace(url('storage') . '/', '', $truk->foto_truk);
                Storage::disk('public')->delete($fotoPath);
            }
    
            if ($truk->qr_code) {
                $qrPath = str_replace(url('storage') . '/', '', $truk->qr_code);
                Storage::disk('public')->delete($qrPath);
            }
    
            $truk->delete();
    
            return response()->json([
                'status'  => 200,
                'message' => 'Data truk berhasil dihapus'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => 'Gagal menghapus data truk',
                'error'   => $e->getMessage()
            ], 400);
        }
    }
    

}
