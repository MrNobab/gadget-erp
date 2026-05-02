<?php

namespace App\Tenant\Http\Controllers;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Inventory\Services\StockTransferTaskSummary;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Platform\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    public function index(Request $request, Tenant $tenant, StockTransferTaskSummary $transferTaskSummary): View
    {
        $tenant->loadMissing('license.plan');

        $currentUser = User::query()
            ->where('id', $request->session()->get('tenant_user_id'))
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $today = now()->toDateString();
        $transferSummary = $transferTaskSummary->summary();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'currentUser' => $currentUser,
            'dashboardStats' => [
                'today_sales' => Invoice::query()
                    ->where('status', Invoice::STATUS_POSTED)
                    ->whereDate('invoice_date', $today)
                    ->sum('total'),
                'today_invoice_count' => Invoice::query()
                    ->where('status', Invoice::STATUS_POSTED)
                    ->whereDate('invoice_date', $today)
                    ->count(),
                'today_collections' => Payment::query()
                    ->whereDate('paid_at', $today)
                    ->sum('amount'),
                'outstanding_due' => Invoice::query()
                    ->where('status', Invoice::STATUS_POSTED)
                    ->sum('due_amount'),
                'due_invoice_count' => Invoice::query()
                    ->where('status', Invoice::STATUS_POSTED)
                    ->where('due_amount', '>', 0)
                    ->count(),
                'stock_value' => WarehouseStock::query()->sum('stock_value'),
                'low_stock_count' => WarehouseStock::query()
                    ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                    ->where('products.tenant_id', $tenant->id)
                    ->whereColumn('warehouse_stocks.quantity', '<=', 'products.low_stock_threshold')
                    ->count('warehouse_stocks.id'),
                'active_product_count' => Product::query()->where('is_active', true)->count(),
                'active_customer_count' => Customer::query()->where('is_active', true)->count(),
                'transfer_task_count' => $transferSummary['total'],
                'warehouse_task_count' => $transferSummary['warehouse']['total'],
                'shop_task_count' => $transferSummary['shop']['total'],
            ],
            'recentInvoices' => Invoice::query()
                ->with(['customer', 'warehouse'])
                ->where('status', Invoice::STATUS_POSTED)
                ->latest('invoice_date')
                ->latest('id')
                ->limit(5)
                ->get(),
            'dashboardTransferSummary' => $transferSummary,
        ]);
    }
}
