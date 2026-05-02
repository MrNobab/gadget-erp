@extends('layouts.tenant')

@section('content')
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-red-200 p-6">
        <div class="text-sm font-semibold text-red-600 mb-2">Access Blocked</div>
        <h2 class="text-2xl font-bold mb-3">{{ $title }}</h2>
        <p class="text-slate-600">{{ $message }}</p>

        <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm">
            <div><strong>Shop:</strong> {{ $tenant->name }}</div>
            <div><strong>Slug:</strong> {{ $tenant->slug }}</div>
        </div>

        <form method="POST" action="{{ route('tenant.logout', $tenant) }}" class="mt-6">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Logout
            </button>
        </form>
    </div>
@endsection
