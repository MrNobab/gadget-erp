<?php

namespace App\Tenant\Http\Controllers\Catalog;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantCategoryController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));

        $categories = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $parentCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('tenant.catalog.categories.index', [
            'tenant' => $tenant,
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'search' => $search,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = $this->uniqueSlug($validated['name']);

        Category::query()->create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('tenant.categories.index', $tenant)
            ->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Tenant $tenant, int $categoryId): RedirectResponse
    {
        $category = Category::query()->findOrFail($categoryId);

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ((int) ($validated['parent_id'] ?? 0) === (int) $category->id) {
            return back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
        }

        $category->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], $category->id),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('tenant.categories.index', $tenant)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Tenant $tenant, int $categoryId): RedirectResponse
    {
        $category = Category::query()->findOrFail($categoryId);

        $hasProducts = Product::query()
            ->where('category_id', $category->id)
            ->exists();

        if ($hasProducts) {
            return back()->withErrors(['category' => 'This category has products. Move or delete those products first.']);
        }

        $hasChildren = Category::query()
            ->where('parent_id', $category->id)
            ->exists();

        if ($hasChildren) {
            return back()->withErrors(['category' => 'This category has child categories. Delete child categories first.']);
        }

        $category->delete();

        return redirect()
            ->route('tenant.categories.index', $tenant)
            ->with('success', 'Category deleted successfully.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Category::query()
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
