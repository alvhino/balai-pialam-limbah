<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $primaryKey = 'uid_transaksi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['uid_transaksi', 'uid_kunjungan', 'uid_ph', 'uid_volume', 'total'];

    public function kunjungan() {
        return $this->belongsTo(Kunjungan::class, 'uid_kunjungan', 'uid_kunjungan');
    }

    public function cek_ph() {
        return $this->belongsTo(CekPh::class, 'uid_ph', 'uid_ph');
    }

    public function volume() {
        return $this->belongsTo(Volume::class, 'uid_volume', 'uid_volume');
    }
}
