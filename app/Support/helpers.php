<?php

use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;

if (! function_exists('nxpbd_money')) {
    function nxpbd_money(float|int|string|null $amount, ?Tenant $tenant = null): string
    {
        $tenant = $tenant ?: TenantContext::get();
        $settings = $tenant?->settings ?? [];

        $symbol = (string) ($settings['currency_symbol'] ?? '৳');
        $position = (string) ($settings['currency_position'] ?? 'before');
        $decimals = (int) ($settings['currency_decimals'] ?? 2);

        $formatted = number_format((float) $amount, $decimals);

        return $position === 'after'
            ? $formatted . $symbol
            : $symbol . $formatted;
    }
}

if (! function_exists('nxpbd_setting')) {
    function nxpbd_setting(string $key, mixed $default = null, ?Tenant $tenant = null): mixed
    {
        $tenant = $tenant ?: TenantContext::get();
        $settings = $tenant?->settings ?? [];

        return $settings[$key] ?? $default;
    }
}
