<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Platform\Models\License;
use App\Platform\Models\Plan;
use App\Platform\Models\SuperAdmin;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SuperAdminAuthController extends Controller
{
    public function loginForm(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('super_admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $superAdmin = SuperAdmin::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $superAdmin || ! Hash::check($credentials['password'], $superAdmin->password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('super_admin_id', $superAdmin->id);
        $request->session()->put('super_admin_name', $superAdmin->name);

        return redirect()->route('admin.dashboard');
    }

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'planCount' => Plan::query()->count(),
            'activeLicenseCount' => License::query()->where('status', License::STATUS_ACTIVE)->count(),
            'frozenLicenseCount' => License::query()->where('status', License::STATUS_FROZEN)->count(),
            'suspendedLicenseCount' => License::query()->where('status', License::STATUS_SUSPENDED)->count(),
            'expiredLicenseCount' => License::query()->where('status', License::STATUS_EXPIRED)->count(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'super_admin_id',
            'super_admin_name',
        ]);

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
