<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable

{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'uid_user';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['uid_user','nama', 'no_hp', 'password', 'foto_ktp', 'role'];

    public function truks() {
        return $this->hasMany(Truk::class, 'uid_user', 'uid_user');
    }
}
