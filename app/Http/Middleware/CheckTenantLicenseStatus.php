<?php

namespace App\Http\Middleware;

use App\Platform\Models\License;
use App\Platform\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantLicenseStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (! $tenant instanceof Tenant) {
            abort(404, 'Tenant not found.');
        }

        $tenant->loadMissing('license.plan');

        if (! $tenant->license) {
            return response()->view('tenant.license-blocked', [
                'tenant' => $tenant,
                'title' => 'License Missing',
                'message' => 'This shop does not have an active license. Please contact support.',
            ], 403);
        }

        if ($tenant->license->status === License::STATUS_ACTIVE) {
            return $next($request);
        }

        $message = match ($tenant->license->status) {
            License::STATUS_FROZEN => 'This shop license is frozen. Read/write access is currently disabled. Please contact support.',
            License::STATUS_SUSPENDED => 'This shop license is suspended. Please contact support.',
            License::STATUS_EXPIRED => 'This shop license has expired. Please renew the license.',
            default => 'This shop license is not active. Please contact support.',
        };

        return response()->view('tenant.license-blocked', [
            'tenant' => $tenant,
            'title' => 'License Blocked',
            'message' => $message,
        ], 403);
    }
}
