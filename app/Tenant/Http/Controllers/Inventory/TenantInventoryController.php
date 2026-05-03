<?php

namespace App\Tenant\Http\Controllers\Inventory;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Purchasing\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TenantInventoryController extends Controller
{
    public function stockIndex(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));
        $warehouseId = $request->query('warehouse_id');

        $stocks = WarehouseStock::query()
            ->with(['warehouse', 'product.category', 'product.brand'])
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('product', function ($productQuery) use ($search): void {
                    $productQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.inventory.stock.index', [
            'tenant' => $tenant,
            'stocks' => $stocks,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'search' => $search,
            'warehouseId' => $warehouseId,
        ]);
    }

    public function stockInCreate(Tenant $tenant): View
    {
        return view('tenant.inventory.stock-in.create', [
            'tenant' => $tenant,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function stockInStore(Request $request, Tenant $tenant, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $inventoryService->stockIn($validated, (int) $request->session()->get('tenant_user_id'));
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['stock' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('tenant.stock.index', $tenant)
            ->with('success', 'Stock added and weighted average cost updated successfully.');
    }

    public function adjustmentCreate(Tenant $tenant): View
    {
        return view('tenant.inventory.adjustments.create', [
            'tenant' => $tenant,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function adjustmentStore(Request $request, Tenant $tenant, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'adjustment_quantity' => ['required', 'integer', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $inventoryService->adjustStock($validated, (int) $request->session()->get('tenant_user_id'));
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['stock' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('tenant.stock.index', $tenant)
            ->with('success', 'Stock adjustment saved successfully.');
    }

    public function movementIndex(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));
        $type = $request->query('type');

        $movements = StockMovement::query()
            ->with(['warehouse', 'product', 'creator'])
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('product', function ($productQuery) use ($search): void {
                    $productQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('tenant.inventory.movements.index', [
            'tenant' => $tenant,
            'movements' => $movements,
            'search' => $search,
            'type' => $type,
        ]);
    }
}
