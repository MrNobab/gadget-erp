@extends('layouts.app')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-2xl font-bold mb-2">Laravel is working on Hostinger</h2>
        <p class="text-slate-600">
            We are running without Node/NPM for now. Blade, Livewire, and Tailwind CDN are ready.
        </p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-lg bg-slate-50 border">
                <div class="text-sm text-slate-500">Next Module</div>
                <div class="text-lg font-semibold">Super Admin</div>
            </div>

            <div class="p-4 rounded-lg bg-slate-50 border">
                <div class="text-sm text-slate-500">Database</div>
                <div class="text-lg font-semibold">MySQL Required</div>
            </div>

            <div class="p-4 rounded-lg bg-slate-50 border">
                <div class="text-sm text-slate-500">Frontend</div>
                <div class="text-lg font-semibold">No NPM Needed Now</div>
            </div>
        </div>
    </div>
@endsection
