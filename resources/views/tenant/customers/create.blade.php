@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Add Customer</h2>
        <p class="text-slate-500">Create a new customer profile.</p>
    </div>

    <form method="POST" action="{{ route('tenant.customers.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-3xl space-y-5">
        @csrf

        @include('tenant.customers.form', [
            'customer' => null,
            'submitText' => 'Save Customer',
        ])
    </form>
@endsection
