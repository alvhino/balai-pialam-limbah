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
  
    public function store(Request $request)
    {
        $request->validate([
            'nama_user'   => 'required|string',
            'input_nopol' => 'required|string|max:30',
            'volume'      => 'required|numeric',
            'foto_truk'   => 'required|image|max:2048',
        ]);
    
        $user = User::where('nama', $request->nama_user)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    
        $fotoPath = $request->file('foto_truk')->store('foto_truk', 'public');
        $fotoUrl = url('storage/' . $fotoPath);
    
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
            'message'     => 'Truk berhasil disimpan',
            'download_qr' => route('download.qr', ['filename' => $qrFilename]),
            'data'        => $truk
        ], 201, [], JSON_UNESCAPED_SLASHES);
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
}
