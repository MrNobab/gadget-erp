<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Platform\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $tenant = $request->route('tenant');
        $userId = $request->session()->get('tenant_user_id');

        if (! $tenant instanceof Tenant || ! $userId) {
            abort(403, 'Tenant user role could not be verified.');
        }

        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', $userId)
            ->where('is_active', true)
            ->first();

        if (! $user || ! $user->hasTenantRole($roles)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
