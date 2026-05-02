<?php

namespace App\Tenant\Http\Controllers\Customers;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerNote;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantCustomerController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');
        $dueFilter = $request->query('due_filter');

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($dueFilter === 'has_due', fn ($query) => $query->where('total_due', '>', 0))
            ->when($dueFilter === 'no_due', fn ($query) => $query->where('total_due', '<=', 0))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.customers.index', [
            'tenant' => $tenant,
            'customers' => $customers,
            'search' => $search,
            'status' => $status,
            'dueFilter' => $dueFilter,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        return view('tenant.customers.create', [
            'tenant' => $tenant,
            'customer' => null,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $customer = Customer::query()->create($validated);

        return redirect()
            ->route('tenant.customers.show', [$tenant, $customer->id])
            ->with('success', 'Customer created successfully.');
    }

    public function show(Tenant $tenant, int $customerId): View
    {
        $customer = Customer::query()
            ->with(['notes.creator'])
            ->findOrFail($customerId);

        return view('tenant.customers.show', [
            'tenant' => $tenant,
            'customer' => $customer,
        ]);
    }

    public function edit(Tenant $tenant, int $customerId): View
    {
        $customer = Customer::query()->findOrFail($customerId);

        return view('tenant.customers.edit', [
            'tenant' => $tenant,
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Tenant $tenant, int $customerId): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customerId);

        $validated = $this->validatedData($request, $customer->id);

        $customer->update($validated);

        return redirect()
            ->route('tenant.customers.show', [$tenant, $customer->id])
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Tenant $tenant, int $customerId): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customerId);

        if ((float) $customer->total_due > 0) {
            return back()->withErrors([
                'customer' => 'This customer has due balance. You cannot delete this customer.',
            ]);
        }

        $customer->delete();

        return redirect()
            ->route('tenant.customers.index', $tenant)
            ->with('success', 'Customer deleted successfully.');
    }

    public function storeNote(Request $request, Tenant $tenant, int $customerId): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customerId);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        CustomerNote::query()->create([
            'customer_id' => $customer->id,
            'note' => $validated['note'],
            'created_by' => (int) $request->session()->get('tenant_user_id'),
        ]);

        return redirect()
            ->route('tenant.customers.show', [$tenant, $customer->id])
            ->with('success', 'Customer note added successfully.');
    }

    private function validatedData(Request $request, ?int $customerId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('customers', 'phone')
                    ->where('tenant_id', TenantContext::id())
                    ->ignore($customerId),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
