<?php

namespace App\Http\Middleware;

use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContextBySlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (! $tenant instanceof Tenant) {
            abort(404, 'Tenant not found.');
        }

        TenantContext::set($tenant);

        return $next($request);
    }
}
