@extends('layouts.tenant')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">Mobile Barcode Scanner</h2>
            <p class="text-slate-500">Open POS on a computer, start mobile pairing, then enter the pairing code here.</p>
        </div>

        <form method="POST" action="{{ route('tenant.mobile-scanner.connect', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700">Pairing Code</label>
                <input type="text" name="pair_code" value="{{ old('pair_code') }}" maxlength="12" autocomplete="off" autofocus placeholder="Example: A1B2C3" class="mt-1 w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-2xl font-bold tracking-widest uppercase">
            </div>

            <button type="submit" class="w-full px-4 py-3 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Connect Phone Scanner
            </button>
        </form>

        @if($sessions->isNotEmpty())
            <div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200">
                    <h3 class="font-bold">Active Pairings</h3>
                    <p class="text-sm text-slate-500">Use the pairing code shown on the POS screen.</p>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($sessions as $session)
                        <form method="POST" action="{{ route('tenant.mobile-scanner.connect', $tenant) }}" class="px-5 py-4 flex items-center justify-between gap-4">
                            @csrf
                            <input type="hidden" name="pair_code" value="{{ $session->pair_code }}">

                            <div>
                                <div class="font-semibold">{{ $session->name ?: 'POS scanner' }}</div>
                                <div class="text-xs text-slate-500">
                                    Code {{ $session->pair_code }} / expires {{ $session->expires_at?->format('h:i A') }}
                                </div>
                            </div>

                            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                                Connect
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
