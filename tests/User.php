<?php

namespace Tests;

use Actengage\Roles\HasRoles;
use Illuminate\Foundation\Auth\User as Model;

class User extends Model
{
    use HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password'
    ];

}
