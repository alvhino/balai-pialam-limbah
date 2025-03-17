<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Volume extends Model
{
    use HasFactory;

    protected $table = 'volumes';
    protected $primaryKey = 'uid_volume';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['uid_volume', 'uid_truk', 'foto', 'total_volume'];

    public function truk()
    {
        return $this->belongsTo(Truk::class, 'uid_truk', 'uid_truk');
    }
}
