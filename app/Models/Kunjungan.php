<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    use HasFactory;

    protected $table = 'kunjungans';
    protected $primaryKey = 'uid_kunjungan';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['uid_kunjungan', 'uid_truk', 'tanggal', 'status', 'jam_kunjungan'];

    protected $casts = [
        'jam_kunjungan' => 'array',
    ];

    public function truk() {
        return $this->belongsTo(Truk::class, 'uid_truk', 'uid_truk');
    }

    public function cek_ph() {
        return $this->hasOne(CekPh::class, 'uid_kunjungan', 'uid_kunjungan');
    }

    public function volume() {
        return $this->hasOne(Volume::class, 'uid_kunjungan', 'uid_kunjungan');
    }

    public function transaksi() {
        return $this->hasOne(Transaksi::class, 'uid_kunjungan', 'uid_kunjungan');
    }
}
