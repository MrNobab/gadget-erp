@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Edit Customer</h2>
        <p class="text-slate-500">Update customer information.</p>
    </div>

    <form method="POST" action="{{ route('tenant.customers.update', [$tenant, $customer->id]) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-3xl space-y-5">
        @csrf
        @method('PUT')

        @include('tenant.customers.form', [
            'customer' => $customer,
            'submitText' => 'Update Customer',
        ])
    </form>
@endsection
