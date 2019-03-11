<?php

namespace Tests;

use Actengage\Roles\Roleable;
use Illuminate\Foundation\Auth\User as Model;

class User extends Model
{
    use Roleable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password'
    ];

}
