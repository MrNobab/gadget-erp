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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $superAdmin = SuperAdmin::query()->find(session('super_admin_id'));

        return view('admin.dashboard', [
            'superAdmin' => $superAdmin,
            'tenantCount' => Tenant::query()->count(),
            'planCount' => Plan::query()->count(),
            'activeLicenseCount' => License::query()->where('status', License::STATUS_ACTIVE)->count(),
            'frozenLicenseCount' => License::query()->where('status', License::STATUS_FROZEN)->count(),
            'suspendedLicenseCount' => License::query()->where('status', License::STATUS_SUSPENDED)->count(),
            'expiredLicenseCount' => License::query()->where('status', License::STATUS_EXPIRED)->count(),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_name' => ['nullable', 'string', 'max:100'],
            'brand_tagline' => ['nullable', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $superAdmin = SuperAdmin::query()->findOrFail($request->session()->get('super_admin_id'));

        if ($request->boolean('remove_logo') && $superAdmin->logo_path) {
            Storage::disk('public')->delete($superAdmin->logo_path);
            $superAdmin->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($superAdmin->logo_path) {
                Storage::disk('public')->delete($superAdmin->logo_path);
            }

            $superAdmin->logo_path = $request->file('logo')->store('platform-branding', 'public');
        }

        $superAdmin->brand_name = $validated['brand_name'] ?: null;
        $superAdmin->brand_tagline = $validated['brand_tagline'] ?: null;
        $superAdmin->save();

        return back()->with('success', 'ERP owner branding updated successfully.');
    }

    public function platformLogo()
    {
        if (! Schema::hasColumn('super_admins', 'logo_path')) {
            abort(404, 'ERP owner logo not found.');
        }

        $superAdmin = SuperAdmin::query()
            ->whereNotNull('logo_path')
            ->latest('updated_at')
            ->first();

        if (! $superAdmin || ! Storage::disk('public')->exists($superAdmin->logo_path)) {
            abort(404, 'ERP owner logo not found.');
        }

        return response()->file(Storage::disk('public')->path($superAdmin->logo_path), [
            'Cache-Control' => 'public, max-age=86400',
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
