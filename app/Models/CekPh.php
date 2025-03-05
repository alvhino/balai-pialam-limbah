<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CekPh extends Model
{
    use HasFactory;

    protected $table = 'cek_ph';
    protected $primaryKey = 'uid_ph';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['uid_ph', 'uid_kunjungan', 'total_ph', 'foto', 'tingkat_ph', 'jenis_limbah'];

    public function kunjungan() {
        return $this->belongsTo(Kunjungan::class, 'uid_kunjungan', 'uid_kunjungan');
    }
}
