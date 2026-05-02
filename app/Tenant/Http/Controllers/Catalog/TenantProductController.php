<?php

namespace App\Tenant\Http\Controllers\Catalog;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantProductController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));
        $categoryId = $request->query('category_id');
        $brandId = $request->query('brand_id');
        $status = $request->query('status');

        $products = Product::query()
            ->with(['category', 'brand'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.catalog.products.index', [
            'tenant' => $tenant,
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'search' => $search,
            'categoryId' => $categoryId,
            'brandId' => $brandId,
            'status' => $status,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        return view('tenant.catalog.products.create', [
            'tenant' => $tenant,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $this->validatedData($request);

        Product::query()->create($validated);

        return redirect()
            ->route('tenant.products.index', $tenant)
            ->with('success', 'Product created successfully.');
    }

    public function edit(Tenant $tenant, int $productId): View
    {
        $product = Product::query()->findOrFail($productId);

        return view('tenant.catalog.products.edit', [
            'tenant' => $tenant,
            'product' => $product,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, int $productId): RedirectResponse
    {
        $product = Product::query()->findOrFail($productId);

        $validated = $this->validatedData($request, $product->id);

        $product->update($validated);

        return redirect()
            ->route('tenant.products.index', $tenant)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Tenant $tenant, int $productId): RedirectResponse
    {
        $product = Product::query()->findOrFail($productId);

        $product->delete();

        return redirect()
            ->route('tenant.products.index', $tenant)
            ->with('success', 'Product deleted successfully.');
    }

    private function validatedData(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'name' => ['required', 'string', 'max:180'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where('tenant_id', TenantContext::id())
                    ->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'warranty_duration_months' => ['required', 'integer', 'min:0', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
