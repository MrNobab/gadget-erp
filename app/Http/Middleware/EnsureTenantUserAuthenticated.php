<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Platform\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUserAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (! $tenant instanceof Tenant) {
            abort(404, 'Tenant not found.');
        }

        $userId = $request->session()->get('tenant_user_id');
        $tenantId = $request->session()->get('tenant_id');

        if (! $userId || (int) $tenantId !== (int) $tenant->id) {
            return redirect()->route('tenant.login', $tenant);
        }

        $user = User::query()
            ->where('id', $userId)
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            $request->session()->forget([
                'tenant_user_id',
                'tenant_id',
                'tenant_user_name',
                'tenant_user_email',
                'tenant_user_role',
            ]);

            return redirect()->route('tenant.login', $tenant);
        }

        $request->session()->put('tenant_user_role', $user->tenantRole());

        return $next($request);
    }
}
