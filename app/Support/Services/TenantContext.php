<?php

namespace App\Support\Services;

use App\Platform\Models\Tenant;
use RuntimeException;

class TenantContext
{
    private static ?Tenant $tenant = null;

    public static function set(Tenant $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function get(): ?Tenant
    {
        return self::$tenant;
    }

    public static function has(): bool
    {
        return self::$tenant instanceof Tenant;
    }

    public static function id(): int
    {
        if (! self::$tenant) {
            throw new RuntimeException('Tenant context has not been set for this request.');
        }

        return (int) self::$tenant->id;
    }

    public static function clear(): void
    {
        self::$tenant = null;
    }
}
