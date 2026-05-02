<?php

namespace App\Platform\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_name',
        'owner_email',
        'owner_phone',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
