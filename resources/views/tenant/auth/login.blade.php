@extends('layouts.tenant')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">{{ $tenant->name }}</h2>
            <p class="text-slate-500">Login to your shop dashboard.</p>
        </div>

        <form method="POST" action="{{ route('tenant.login.submit', $tenant) }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-900 focus:outline-none"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-900 focus:outline-none"
                >
            </div>

            <button type="submit" class="w-full rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">
                Login
            </button>
        </form>

        <div class="mt-6 text-sm text-slate-500">
            Tenant URL:
            <span class="font-mono">/shop/{{ $tenant->slug }}</span>
        </div>
    </div>
@endsection
