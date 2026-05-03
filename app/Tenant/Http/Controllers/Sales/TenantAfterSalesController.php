<?php

namespace App\Tenant\Http\Controllers\Sales;

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\InvoiceItem;
use App\Domain\Sales\Models\SalesReturn;
use App\Domain\Sales\Models\WarrantyClaim;
use App\Domain\Sales\Services\AfterSalesService;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TenantAfterSalesController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('tenant.sales.after-sales.index', [
            'tenant' => $tenant,
            'invoiceItems' => InvoiceItem::query()
                ->with(['invoice.customer', 'product', 'warehouse'])
                ->latest()
                ->limit(200)
                ->get(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'returns' => SalesReturn::query()
                ->with(['invoice.customer', 'product', 'warehouse', 'creator'])
                ->latest('returned_at')
                ->limit(30)
                ->get(),
            'warrantyClaims' => WarrantyClaim::query()
                ->with(['customer', 'invoice', 'product', 'creator'])
                ->latest('opened_at')
                ->limit(30)
                ->get(),
        ]);
    }

    public function storeReturn(Request $request, Tenant $tenant, AfterSalesService $afterSalesService): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_item_id' => [
                'required',
                'integer',
                Rule::exists('invoice_items', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'returned_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $afterSalesService->recordReturn($validated, (int) $request->session()->get('tenant_user_id'));
        } catch (Throwable $exception) {
            return back()->withErrors(['return' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Sales return recorded and stock restored.');
    }

    public function storeWarranty(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'invoice_item_id' => [
                'nullable',
                'integer',
                Rule::exists('invoice_items', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'claim_type' => ['required', 'in:repair,replacement,service_check'],
            'opened_at' => ['required', 'date'],
            'issue' => ['required', 'string', 'max:2000'],
        ]);

        $invoiceItem = ! empty($validated['invoice_item_id'])
            ? InvoiceItem::query()->findOrFail((int) $validated['invoice_item_id'])
            : null;

        WarrantyClaim::query()->create([
            'customer_id' => (int) $validated['customer_id'],
            'invoice_id' => $invoiceItem?->invoice_id,
            'invoice_item_id' => $invoiceItem?->id,
            'product_id' => $invoiceItem?->product_id,
            'claim_type' => $validated['claim_type'],
            'status' => WarrantyClaim::STATUS_OPEN,
            'opened_at' => $validated['opened_at'],
            'issue' => $validated['issue'],
            'created_by' => (int) $request->session()->get('tenant_user_id'),
        ]);

        return back()->with('success', 'Warranty claim opened successfully.');
    }

    public function updateWarranty(Request $request, Tenant $tenant, int $claimId): RedirectResponse
    {
        $claim = WarrantyClaim::query()->findOrFail($claimId);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,rejected'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'resolved_at' => ['nullable', 'date'],
        ]);

        $claim->update([
            'status' => $validated['status'],
            'resolution' => $validated['resolution'] ?? null,
            'resolved_at' => in_array($validated['status'], [WarrantyClaim::STATUS_RESOLVED, WarrantyClaim::STATUS_REJECTED], true)
                ? ($validated['resolved_at'] ?? now()->toDateString())
                : null,
        ]);

        return back()->with('success', 'Warranty claim updated successfully.');
    }
}
