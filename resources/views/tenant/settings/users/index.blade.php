@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Staff Users</h2>
            <p class="text-slate-500">Create staff accounts, assign roles, and deactivate access when needed.</p>
        </div>

        <a href="{{ route('tenant.dashboard', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('tenant.users.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            @csrf

            <h3 class="font-bold text-lg">Add Staff</h3>

            <div>
                <label class="block text-sm font-medium text-slate-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Role</label>
                <select name="role" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach(collect($roles)->except('owner') as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Confirm</label>
                    <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                <span class="text-sm text-slate-700">Active user</span>
            </label>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Create User
            </button>
        </form>

        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">User</th>
                        <th class="text-left px-4 py-3">Role</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Last Login</th>
                        <th class="text-left px-4 py-3">Update</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                    {{ $roles[$user->tenantRole()] ?? ucfirst($user->tenantRole()) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Active</span>
                                @else
                                    <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $user->last_login_at?->format('d M Y h:i A') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <details>
                                    <summary class="cursor-pointer font-semibold text-slate-900">Edit</summary>

                                    <form method="POST" action="{{ route('tenant.users.update', [$tenant, $user->id]) }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 min-w-96">
                                        @csrf
                                        @method('PUT')

                                        <input type="text" name="name" value="{{ $user->name }}" required class="rounded-lg border border-slate-300 px-3 py-2">
                                        <input type="email" name="email" value="{{ $user->email }}" required class="rounded-lg border border-slate-300 px-3 py-2">

                                        <select name="role" required @disabled($user->is_owner) class="rounded-lg border border-slate-300 px-3 py-2">
                                            @foreach($roles as $value => $label)
                                                <option value="{{ $value }}" @selected($user->tenantRole() === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @if($user->is_owner)
                                            <input type="hidden" name="role" value="owner">
                                            <input type="hidden" name="is_active" value="1">
                                        @endif

                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($user->is_active) @disabled($user->is_owner) class="rounded border-slate-300">
                                            Active
                                        </label>

                                        <input type="password" name="password" placeholder="New password" class="rounded-lg border border-slate-300 px-3 py-2">
                                        <input type="password" name="password_confirmation" placeholder="Confirm password" class="rounded-lg border border-slate-300 px-3 py-2">

                                        <button type="submit" class="md:col-span-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                                            Save Changes
                                        </button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
