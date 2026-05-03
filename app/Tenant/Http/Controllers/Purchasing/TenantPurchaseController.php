<?php

namespace App\Tenant\Http\Controllers\Purchasing;

use App\Domain\Inventory\Models\ProductPurchaseLot;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\View\View;

class TenantPurchaseController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('tenant.purchasing.purchases.index', [
            'tenant' => $tenant,
            'purchases' => ProductPurchaseLot::query()
                ->with(['warehouse', 'product', 'supplier', 'creator'])
                ->latest('purchased_at')
                ->latest('id')
                ->paginate(25),
        ]);
    }
}
