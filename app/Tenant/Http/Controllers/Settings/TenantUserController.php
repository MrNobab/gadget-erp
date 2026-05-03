<?php

namespace App\Tenant\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantUserController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('tenant.settings.users.index', [
            'tenant' => $tenant,
            'users' => User::query()
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('is_owner')
                ->orderBy('name')
                ->get(),
            'roles' => User::roleOptions(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(array_keys($this->assignableRoles()))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_owner' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Staff user created successfully.');
    }

    public function update(Request $request, Tenant $tenant, int $userId): RedirectResponse
    {
        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! $user->is_owner) {
            $payload['role'] = $validated['role'];
        }

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return back()->with('success', 'Staff user updated successfully.');
    }

    private function assignableRoles(): array
    {
        return collect(User::roleOptions())
            ->except(User::ROLE_OWNER)
            ->all();
    }
}
