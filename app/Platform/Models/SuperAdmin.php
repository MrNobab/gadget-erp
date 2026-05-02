<?php

namespace App\Platform\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SuperAdmin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'brand_name',
        'brand_tagline',
        'logo_path',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
