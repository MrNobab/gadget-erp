@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Add Product</h2>
        <p class="text-slate-500">Create a product without image upload.</p>
    </div>

    <form method="POST" action="{{ route('tenant.products.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-4xl space-y-5">
        @csrf

        @include('tenant.catalog.products.form', [
            'product' => null,
            'categories' => $categories,
            'brands' => $brands,
            'submitText' => 'Save Product',
        ])
    </form>
@endsection
