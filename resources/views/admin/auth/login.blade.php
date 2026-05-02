@extends('layouts.admin')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-2xl font-bold mb-1">Super Admin Login</h2>
        <p class="text-slate-500 mb-6">Login to manage tenants, plans, and licenses.</p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
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
    </div>
@endsection
