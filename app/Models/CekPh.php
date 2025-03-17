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

    protected $fillable = [
        'uid_ph',
        'uid_truk',
        'uid_kunjungan',
        'biaya',
        'foto',
        'ph',
        'jenis_limbah'
    ];

    public function truk()
    {
        return $this->belongsTo(Truk::class, 'uid_truk', 'uid_truk');
    }
}
