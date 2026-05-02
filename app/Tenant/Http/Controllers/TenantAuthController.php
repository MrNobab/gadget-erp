<?php

namespace App\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantAuthController extends Controller
{
    public function loginForm(Request $request, Tenant $tenant): View|RedirectResponse
    {
        if (
            $request->session()->has('tenant_user_id')
            && (int) $request->session()->get('tenant_id') === (int) $tenant->id
        ) {
            return redirect()->route('tenant.dashboard', $tenant);
        }

        return view('tenant.auth.login', [
            'tenant' => $tenant,
        ]);
    }

    public function login(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->onlyInput('email');
        }

        if (! $user->is_active) {
            return back()
                ->withErrors(['email' => 'This user account is inactive.'])
                ->onlyInput('email');
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $request->session()->regenerate();

        $request->session()->put('tenant_user_id', $user->id);
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_user_name', $user->name);
        $request->session()->put('tenant_user_email', $user->email);

        return redirect()->route('tenant.dashboard', $tenant);
    }

    public function logout(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->session()->forget([
            'tenant_user_id',
            'tenant_id',
            'tenant_user_name',
            'tenant_user_email',
        ]);

        $request->session()->regenerateToken();

        return redirect()->route('tenant.login', $tenant);
    }
}
