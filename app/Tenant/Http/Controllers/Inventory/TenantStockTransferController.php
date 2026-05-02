<?php

namespace App\Tenant\Http\Controllers\Inventory;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferLog;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockTransferService;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TenantStockTransferController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $status = $request->query('status');

        $transfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy', 'acceptedBy', 'sentBy', 'receivedBy'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.inventory.transfers.index', [
            'tenant' => $tenant,
            'transfers' => $transfers,
            'status' => $status,
            'statuses' => [
                StockTransfer::STATUS_REQUESTED,
                StockTransfer::STATUS_ACCEPTED,
                StockTransfer::STATUS_IN_TRANSIT,
                StockTransfer::STATUS_RECEIVED,
                StockTransfer::STATUS_REJECTED,
                StockTransfer::STATUS_CANCELLED,
            ],
        ]);
    }

    public function warehouseTasks(Tenant $tenant): View
    {
        $requestedTransfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy'])
            ->whereHas('source', fn ($query) => $query->where('type', Warehouse::TYPE_WAREHOUSE))
            ->where('status', StockTransfer::STATUS_REQUESTED)
            ->latest()
            ->get();

        $acceptedTransfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy', 'acceptedBy'])
            ->whereHas('source', fn ($query) => $query->where('type', Warehouse::TYPE_WAREHOUSE))
            ->where('status', StockTransfer::STATUS_ACCEPTED)
            ->latest()
            ->get();

        $inTransitTransfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy', 'sentBy'])
            ->whereHas('source', fn ($query) => $query->where('type', Warehouse::TYPE_WAREHOUSE))
            ->where('status', StockTransfer::STATUS_IN_TRANSIT)
            ->latest()
            ->get();

        $receivedUnacknowledgedTransfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy', 'receivedBy'])
            ->whereHas('source', fn ($query) => $query->where('type', Warehouse::TYPE_WAREHOUSE))
            ->where('status', StockTransfer::STATUS_RECEIVED)
            ->whereNull('warehouse_acknowledged_at')
            ->latest()
            ->get();

        return view('tenant.inventory.transfers.warehouse-tasks', [
            'tenant' => $tenant,
            'requestedTransfers' => $requestedTransfers,
            'acceptedTransfers' => $acceptedTransfers,
            'inTransitTransfers' => $inTransitTransfers,
            'receivedUnacknowledgedTransfers' => $receivedUnacknowledgedTransfers,
        ]);
    }

    public function shopTasks(Tenant $tenant): View
    {
        $incomingTransfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy', 'sentBy'])
            ->whereHas('destination', fn ($query) => $query->where('type', Warehouse::TYPE_SHOP))
            ->where('status', StockTransfer::STATUS_IN_TRANSIT)
            ->latest()
            ->get();

        $requestedByShopTransfers = StockTransfer::query()
            ->with(['source', 'destination', 'requestedBy'])
            ->whereHas('destination', fn ($query) => $query->where('type', Warehouse::TYPE_SHOP))
            ->whereIn('status', [
                StockTransfer::STATUS_REQUESTED,
                StockTransfer::STATUS_ACCEPTED,
            ])
            ->latest()
            ->get();

        return view('tenant.inventory.transfers.shop-tasks', [
            'tenant' => $tenant,
            'incomingTransfers' => $incomingTransfers,
            'requestedByShopTransfers' => $requestedByShopTransfers,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('tenant.inventory.transfers.create', [
            'tenant' => $tenant,
            'locations' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
            'products' => $products,
            'productPayload' => $products->mapWithKeys(fn ($product): array => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'label' => $product->name . ' — ' . $product->sku,
                ],
            ])->toArray(),
        ]);
    }

    public function store(Request $request, Tenant $tenant, StockTransferService $service): RedirectResponse
    {
        $validated = $request->validate([
            'source_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'destination_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'request_note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $transfer = $service->requestTransfer(
                $validated,
                (int) $request->session()->get('tenant_user_id')
            );
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['transfer' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('tenant.stock-transfers.show', [$tenant, $transfer->id])
            ->with('success', 'Transfer request created successfully.');
    }

    public function show(Tenant $tenant, int $transferId): View
    {
        $transfer = StockTransfer::query()
            ->with([
                'source',
                'destination',
                'items.product',
                'logs.user',
                'requestedBy',
                'acceptedBy',
                'sentBy',
                'receivedBy',
                'warehouseAcknowledgedBy',
            ])
            ->findOrFail($transferId);

        return view('tenant.inventory.transfers.show', [
            'tenant' => $tenant,
            'transfer' => $transfer,
        ]);
    }

    public function accept(Request $request, Tenant $tenant, int $transferId, StockTransferService $service): RedirectResponse
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);

        try {
            $service->accept($transfer, (int) $request->session()->get('tenant_user_id'), $request->input('note'));
        } catch (Throwable $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transfer accepted successfully.');
    }

    public function send(Request $request, Tenant $tenant, int $transferId, StockTransferService $service): RedirectResponse
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);

        try {
            $service->send($transfer, (int) $request->session()->get('tenant_user_id'), $request->input('note'));
        } catch (Throwable $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transfer sent. Stock is now in transit.');
    }

    public function receive(Request $request, Tenant $tenant, int $transferId, StockTransferService $service): RedirectResponse
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);

        try {
            $service->receive($transfer, (int) $request->session()->get('tenant_user_id'), $request->input('note'));
        } catch (Throwable $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transfer received. Stock added to destination.');
    }

    public function reject(Request $request, Tenant $tenant, int $transferId, StockTransferService $service): RedirectResponse
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);

        try {
            $service->reject($transfer, (int) $request->session()->get('tenant_user_id'), $request->input('note'));
        } catch (Throwable $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transfer rejected successfully.');
    }

    public function acknowledgeReceived(Request $request, Tenant $tenant, int $transferId): RedirectResponse
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);

        if ($transfer->status !== StockTransfer::STATUS_RECEIVED) {
            return back()->withErrors(['transfer' => 'Only received transfers can be acknowledged.']);
        }

        if ($transfer->warehouse_acknowledged_at) {
            return back()->with('success', 'This received transfer is already acknowledged.');
        }

        $userId = (int) $request->session()->get('tenant_user_id');

        $transfer->update([
            'warehouse_acknowledged_by' => $userId,
            'warehouse_acknowledged_at' => now(),
        ]);

        StockTransferLog::query()->create([
            'stock_transfer_id' => $transfer->id,
            'action' => 'warehouse_acknowledged_received',
            'from_status' => StockTransfer::STATUS_RECEIVED,
            'to_status' => StockTransfer::STATUS_RECEIVED,
            'user_id' => $userId,
            'notes' => $request->input('note') ?: 'Warehouse acknowledged that shop received the stock.',
        ]);

        return back()->with('success', 'Received transfer acknowledged and marked as read.');
    }
}
