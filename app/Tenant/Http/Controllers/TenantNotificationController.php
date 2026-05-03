<?php

namespace App\Tenant\Http\Controllers;

use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Inventory\Services\StockTransferTaskSummary;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\WarrantyClaim;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\View\View;

class TenantNotificationController extends Controller
{
    public function index(Tenant $tenant, StockTransferTaskSummary $transferTaskSummary): View
    {
        return view('tenant.notifications.index', [
            'tenant' => $tenant,
            'transferSummary' => $transferTaskSummary->summary(),
            'lowStocks' => WarehouseStock::query()
                ->with(['warehouse', 'product'])
                ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                ->where('products.tenant_id', $tenant->id)
                ->whereColumn('warehouse_stocks.quantity', '<=', 'products.low_stock_threshold')
                ->select('warehouse_stocks.*')
                ->limit(20)
                ->get(),
            'dueInvoices' => Invoice::query()
                ->with(['customer'])
                ->where('status', Invoice::STATUS_POSTED)
                ->where('due_amount', '>', 0)
                ->oldest('invoice_date')
                ->limit(20)
                ->get(),
            'supplierDues' => Supplier::query()
                ->where('total_due', '>', 0)
                ->orderByDesc('total_due')
                ->limit(20)
                ->get(),
            'warrantyClaims' => WarrantyClaim::query()
                ->with(['customer', 'product'])
                ->whereIn('status', [WarrantyClaim::STATUS_OPEN, WarrantyClaim::STATUS_IN_PROGRESS])
                ->oldest('opened_at')
                ->limit(20)
                ->get(),
        ]);
    }
}
