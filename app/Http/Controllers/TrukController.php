<?php

namespace App\Http\Controllers;

use App\Models\Truk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;

class TrukController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $result = DB::selectOne(
                "SELECT get_data_truk(?, ?) AS result",
                [
                    $request->query('nopol'), 
                    $request->query('nama_supir') 
                ]
            );
    
            $response = json_decode($result->result, true);
    
            $data = collect($response['data'])->map(function ($truk, $index) {
                $qrPath = parse_url($truk['qr_code'], PHP_URL_PATH);
                $qrFilename = basename($qrPath);
    
                return array_merge($truk, [
                    'download_qr' => route('download.qr', ['filename' => $qrFilename])
                ]);
            });
    
            $response['data'] = $data;
    
            return response()->json($response, $response['status']);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Terjadi kesalahan saat mengambil data truk',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function Show($uid_truk)
    {
        try {
            $data = DB::select('SELECT * FROM get_detail_truk(?)', [$uid_truk]);
    
            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Data truk tidak ditemukan'
                ], 404);
            }
    
            $data = (array) $data[0];
            $data['foto_ktp'] = url('storage/' . $data['foto_ktp']);
    
            return response()->json([
                'status' => 200,
                'message' => 'Data truk berhasil ditemukan',
                'data' => $data
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            \Log::error('Gagal mengambil data truk: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
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

        $user = DB::table('users')->where('nama', $request->nama_user)->first();
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $existingTruk = DB::table('truks')->where('uid_user', $user->uid_user)->first();
        if ($existingTruk) {
            return response()->json([
                'status' => 409,
                'message' => 'User sudah memiliki truk'
            ], 409);
        }

        $nopolUsed = DB::table('truks')->where('input_nopol', $request->input_nopol)->first();
        if ($nopolUsed) {
            return response()->json([
                'status' => 409,
                'message' => 'Nopol sudah digunakan'
            ], 409);
        }

        $fotoPath = $request->file('foto_truk')->store('foto_truk', 'public');
        $fotoUrl  = url('storage/' . $fotoPath);
        $qrUrl    = null;

        $result = DB::select('SELECT * FROM create_truk(?, ?, ?, ?, ?)', [
            $request->nama_user,
            $request->input_nopol,
            $request->volume,
            $fotoUrl,
            $qrUrl
        ]);

        $truk = $result[0];

        $qrData = $truk->uid_truk;
        $qrName = $truk->uid_truk . '.png';
        $qrPath = 'qr_code/' . $qrName;

        $qrResult = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($qrPath, $qrResult->getString());
        $qrUrl = url('storage/' . $qrPath);

        DB::table('truks')->where('uid_truk', $truk->uid_truk)->update([
            'qr_code' => $qrUrl
        ]);

        $truk->qr_code = $qrUrl;

        return response()->json([
            'status'      => 201,
            'message'     => 'Data Truk berhasil disimpan',
            'download_qr' => route('download.qr', ['filename' => $qrName]),
            'data'        => $truk
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status'  => 422,
            'message' => 'Validasi gagal',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        $msg = $e->getMessage();

        if (str_contains($msg, 'User tidak ditemukan')) {
            $msg = 'User tidak ditemukan';
        } elseif (str_contains($msg, 'User sudah memiliki truk')) {
            $msg = 'User sudah memiliki truk';
        } elseif (str_contains($msg, 'User sudah digunakan di truk lain')) {
            $msg = 'User sudah digunakan di truk lain';
        } elseif (str_contains($msg, 'Nopol sudah digunakan')) {
            $msg = 'Nopol sudah digunakan';
        }

        return response()->json([
            'status'  => 500,
            'message' => $msg
        ], 500);
    }
}  
    
public function update(Request $request, $uid_truk)
{
    try {
        $validatedData = $request->validate([
            'nama_user'   => 'required|string|exists:users,nama',
            'input_nopol' => 'required|string|max:30',
            'volume'      => 'required|numeric|min:0',
            'foto_truk'   => 'nullable|image|max:2048',
        ]);

        $truk = Truk::where('uid_truk', $uid_truk)->first();
        if (!$truk) {
            return response()->json([
                'status'  => 404,
                'message' => 'Data truk tidak ditemukan',
                'errors'  => ['detail' => 'Data truk tidak ditemukan']
            ], 404);
        }

        $user = User::where('nama', $validatedData['nama_user'])->first();
        if (!$user) {
            return response()->json([
                'status'  => 404,
                'message' => 'User tidak ditemukan',
                'errors'  => ['detail' => 'User tidak ditemukan']
            ], 404);
        }

        $fotoUrl = $truk->foto_truk;
        if ($request->hasFile('foto_truk')) {
            $oldPath = str_replace(url('storage') . '/', '', $truk->foto_truk);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $fotoPath = $request->file('foto_truk')->store('foto_truk', 'public');
            $fotoUrl  = url('storage/' . $fotoPath);
        }

        $qrData = json_encode([
            'uid_truk'   => $truk->uid_truk,
            'nama_supir' => $user->nama,
            'no_hp'      => $user->no_hp,
            'nopol'      => $validatedData['input_nopol'],
            'volume'     => $validatedData['volume'],
            'foto_truk'  => $fotoUrl
        ], JSON_UNESCAPED_SLASHES);

        $qrResult = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        $qrFilename = $truk->uid_truk . '.png';
        $qrPath     = 'qr_code/' . $qrFilename;
        Storage::disk('public')->put($qrPath, $qrResult->getString());
        $qrUrl = url('storage/' . $qrPath);

        $result = DB::select('SELECT * FROM update_truk(?, ?, ?, ?, ?, ?)', [
            $uid_truk,
            $user->uid_user,
            $validatedData['input_nopol'],
            $validatedData['volume'],
            $fotoUrl,
            $qrUrl
        ]);

        if (empty($result)) {
            return response()->json([
                'status'  => 500,
                'message' => 'Gagal mengupdate truk',
                'errors'  => ['detail' => 'Gagal mengupdate truk']
            ], 500);
        }

        return response()->json([
            'status'      => 200,
            'message'     => 'Data Truk berhasil diperbarui',
            'download_qr' => route('download.qr', ['filename' => $qrFilename]),
            'data'        => $result[0]
        ], 200, [], JSON_UNESCAPED_SLASHES);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status'  => 422,
            'message' => 'Validasi gagal',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Gagal update truk: ' . $e->getMessage());

        $errorMsg = $e->getMessage();
        $userMessage = match (true) {
            str_contains($errorMsg, 'Nopol sudah digunakan') => 'Nopol sudah digunakan oleh truk lain.',
            str_contains($errorMsg, 'User sudah digunakan')  => 'User sudah digunakan di truk lain.',
            str_contains($errorMsg, 'User tidak ditemukan')  => 'User tidak ditemukan.',
            default                                           => 'Terjadi kesalahan saat mengupdate data truk.'
        };

        return response()->json([
            'status'  => 500,
            'message' => $userMessage,
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
    
            DB::statement("SELECT hapus_truk(?)", [$id]);
    
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
