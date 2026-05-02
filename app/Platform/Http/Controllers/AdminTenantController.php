<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Platform\Models\Plan;
use App\Platform\Models\Tenant;
use App\Platform\Services\TenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminTenantController extends Controller
{
    public function index(): View
    {
        return view('admin.tenants.index', [
            'tenants' => Tenant::query()
                ->with(['license.plan'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.tenants.create', [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->get(),
        ]);
    }

    public function store(Request $request, TenantProvisioner $tenantProvisioner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('tenants', 'slug'),
            ],
            'owner_name' => ['required', 'string', 'max:150'],
            'owner_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'owner_phone' => ['nullable', 'string', 'max:30'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);

        if (Tenant::query()->where('slug', $validated['slug'])->exists()) {
            return back()
                ->withErrors(['slug' => 'This tenant slug is already taken.'])
                ->withInput();
        }

        $tenant = $tenantProvisioner->createTenantWithOwner(
            $validated,
            (int) $request->session()->get('super_admin_id')
        );

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant {$tenant->name} created successfully.");
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['license.plan', 'license.logs.changedBy']);

        return view('admin.tenants.show', [
            'tenant' => $tenant,
        ]);
    }
}
