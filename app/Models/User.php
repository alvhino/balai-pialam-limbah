<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $primaryKey = 'uid_user';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['nama', 'no_hp', 'password', 'foto_ktp', 'role'];

    public function truks() {
        return $this->hasMany(Truk::class, 'uid_user', 'uid_user');
    }
}
