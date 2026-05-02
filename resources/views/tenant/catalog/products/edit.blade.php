@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Edit Product</h2>
        <p class="text-slate-500">Update product information.</p>
    </div>

    <form method="POST" action="{{ route('tenant.products.update', [$tenant, $product->id]) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-4xl space-y-5">
        @csrf
        @method('PUT')

        @include('tenant.catalog.products.form', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'submitText' => 'Update Product',
        ])
    </form>
@endsection
