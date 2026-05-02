<?php

namespace App\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Platform\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $tenant->loadMissing('license.plan');

        $currentUser = User::query()
            ->where('id', $request->session()->get('tenant_user_id'))
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'currentUser' => $currentUser,
            'userCount' => User::query()->where('tenant_id', $tenant->id)->count(),
            'activeUserCount' => User::query()->where('tenant_id', $tenant->id)->where('is_active', true)->count(),
        ]);
    }
}
