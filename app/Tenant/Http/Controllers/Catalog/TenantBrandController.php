<?php

namespace App\Tenant\Http\Controllers\Catalog;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantBrandController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));

        $brands = Brand::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.catalog.brands.index', [
            'tenant' => $tenant,
            'brands' => $brands,
            'search' => $search,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Brand::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('tenant.brands.index', $tenant)
            ->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, Tenant $tenant, int $brandId): RedirectResponse
    {
        $brand = Brand::query()->findOrFail($brandId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $brand->update([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], $brand->id),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('tenant.brands.index', $tenant)
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Tenant $tenant, int $brandId): RedirectResponse
    {
        $brand = Brand::query()->findOrFail($brandId);

        $hasProducts = Product::query()
            ->where('brand_id', $brand->id)
            ->exists();

        if ($hasProducts) {
            return back()->withErrors(['brand' => 'This brand has products. Move or delete those products first.']);
        }

        $brand->delete();

        return redirect()
            ->route('tenant.brands.index', $tenant)
            ->with('success', 'Brand deleted successfully.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Brand::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
