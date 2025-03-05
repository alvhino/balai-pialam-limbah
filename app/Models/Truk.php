<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truk extends Model
{
    use HasFactory;

    protected $table = 'truks';
    protected $primaryKey = 'uid_truk';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['uid_truk', 'uid_user', 'input_nopol', 'qr_code', 'volume', 'foto_truk'];

    public function user() {
        return $this->belongsTo(User::class, 'uid_user', 'uid_user');
    }

    public function kunjungans() {
        return $this->hasMany(Kunjungan::class, 'uid_truk', 'uid_truk');
    }
}
