<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nama',
        'no_hp',
        'username',
        'password',
        'role',
        'wilayah',
    ];

    protected $hidden = [
        'password',
    ];

    public $timestamps = false;
}
