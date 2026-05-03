<?php

namespace App\Tenant\Http\Controllers\Purchasing;

use App\Domain\Purchasing\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantSupplierController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('tenant.purchasing.suppliers.index', [
            'tenant' => $tenant,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        Supplier::query()->create($this->validatedData($request));

        return back()->with('success', 'Supplier created successfully.');
    }

    public function update(Request $request, Tenant $tenant, int $supplierId): RedirectResponse
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $supplier->update($this->validatedData($request, $supplier->id));

        return back()->with('success', 'Supplier updated successfully.');
    }

    private function validatedData(Request $request, ?int $supplierId = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('suppliers', 'name')
                    ->where('tenant_id', $request->route('tenant')->id)
                    ->ignore($supplierId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
