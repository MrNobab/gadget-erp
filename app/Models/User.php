<?php

namespace App\Models;

use App\Platform\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_CASHIER = 'cashier';
    public const ROLE_WAREHOUSE = 'warehouse';
    public const ROLE_ACCOUNTANT = 'accountant';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_owner',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_OWNER => 'Owner',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_CASHIER => 'Cashier',
            self::ROLE_WAREHOUSE => 'Warehouse Staff',
            self::ROLE_ACCOUNTANT => 'Accountant',
        ];
    }

    public function tenantRole(): string
    {
        if ($this->is_owner) {
            return self::ROLE_OWNER;
        }

        return $this->role ?: self::ROLE_MANAGER;
    }

    public function hasTenantRole(array|string $roles): bool
    {
        return in_array($this->tenantRole(), (array) $roles, true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
