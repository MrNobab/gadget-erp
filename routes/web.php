<?php

use App\Http\Middleware\CheckTenantLicenseStatus;
use App\Http\Middleware\EnsureSuperAdminAuthenticated;
use App\Http\Middleware\EnsureTenantUserAuthenticated;
use App\Http\Middleware\SetTenantContextBySlug;
use App\Platform\Http\Controllers\AdminPlanController;
use App\Platform\Http\Controllers\AdminTenantController;
use App\Platform\Http\Controllers\SuperAdminAuthController;
use App\Tenant\Http\Controllers\Accounting\TenantAccountingController;
use App\Tenant\Http\Controllers\Catalog\TenantBrandController;
use App\Tenant\Http\Controllers\Catalog\TenantCategoryController;
use App\Tenant\Http\Controllers\Catalog\TenantProductController;
use App\Tenant\Http\Controllers\Customers\TenantCustomerController;
use App\Tenant\Http\Controllers\Inventory\TenantInventoryController;
use App\Tenant\Http\Controllers\Inventory\TenantStockTransferController;
use App\Tenant\Http\Controllers\Sales\TenantSalesController;
use App\Tenant\Http\Controllers\Settings\TenantShopSettingController;
use App\Tenant\Http\Controllers\TenantAuthController;
use App\Tenant\Http\Controllers\TenantDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('test');
});

Route::prefix('admin')->group(function (): void {
    Route::get('/login', [SuperAdminAuthController::class, 'loginForm'])->name('admin.login');
    Route::post('/login', [SuperAdminAuthController::class, 'login'])->name('admin.login.submit');

    Route::middleware(EnsureSuperAdminAuthenticated::class)->group(function (): void {
        Route::get('/dashboard', [SuperAdminAuthController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/logout', [SuperAdminAuthController::class, 'logout'])->name('admin.logout');

        Route::get('/plans', [AdminPlanController::class, 'index'])->name('admin.plans.index');
        Route::get('/plans/create', [AdminPlanController::class, 'create'])->name('admin.plans.create');
        Route::post('/plans', [AdminPlanController::class, 'store'])->name('admin.plans.store');

        Route::get('/tenants', [AdminTenantController::class, 'index'])->name('admin.tenants.index');
        Route::get('/tenants/create', [AdminTenantController::class, 'create'])->name('admin.tenants.create');
        Route::post('/tenants', [AdminTenantController::class, 'store'])->name('admin.tenants.store');
        Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show'])->name('admin.tenants.show');
    });
});

Route::prefix('shop/{tenant:slug}')->group(function (): void {
    Route::get('/login', [TenantAuthController::class, 'loginForm'])->name('tenant.login');
    Route::post('/login', [TenantAuthController::class, 'login'])->name('tenant.login.submit');
    Route::post('/logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');

    Route::middleware([
        SetTenantContextBySlug::class,
        EnsureTenantUserAuthenticated::class,
        CheckTenantLicenseStatus::class,
    ])->group(function (): void {
        Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');

        Route::get('/settings/shop/logo', [TenantShopSettingController::class, 'logo'])->name('tenant.settings.shop.logo');







        Route::get('/accounting/daily-summary', [TenantAccountingController::class, 'dailySummary'])->name('tenant.accounting.daily-summary');
        Route::get('/accounting/daily-summary/download', [TenantAccountingController::class, 'downloadDailySummary'])->name('tenant.accounting.daily-summary.download');

        Route::get('/accounting/ledger', [TenantAccountingController::class, 'ledger'])->name('tenant.accounting.ledger');
        Route::get('/accounting/ledger/download', [TenantAccountingController::class, 'downloadLedger'])->name('tenant.accounting.ledger.download');

        Route::get('/accounting/cashbook', [TenantAccountingController::class, 'cashbook'])->name('tenant.accounting.cashbook');
        Route::get('/accounting/cashbook/download', [TenantAccountingController::class, 'downloadCashbook'])->name('tenant.accounting.cashbook.download');

        Route::get('/accounting/sales-ledger', [TenantAccountingController::class, 'salesLedger'])->name('tenant.accounting.sales-ledger');
        Route::get('/accounting/sales-ledger/download', [TenantAccountingController::class, 'downloadSalesLedger'])->name('tenant.accounting.sales-ledger.download');

        Route::get('/accounting/customer-ledger', [TenantAccountingController::class, 'customerLedger'])->name('tenant.accounting.customer-ledger');
        Route::get('/accounting/customer-ledger/download', [TenantAccountingController::class, 'downloadCustomerLedger'])->name('tenant.accounting.customer-ledger.download');
        Route::post('/accounting/customer-dues/{customerId}/collect', [TenantAccountingController::class, 'collectCustomerDue'])->name('tenant.accounting.customer-dues.collect');

        Route::get('/accounting/due-collections', [TenantAccountingController::class, 'dueCollections'])->name('tenant.accounting.due-collections');
        Route::get('/accounting/due-collections/download', [TenantAccountingController::class, 'downloadDueCollections'])->name('tenant.accounting.due-collections.download');
        Route::post('/accounting/invoice-dues/{invoiceId}/collect', [TenantAccountingController::class, 'collectInvoiceDue'])->name('tenant.accounting.invoice-dues.collect');

        Route::get('/accounting/expenses', [TenantAccountingController::class, 'expenses'])->name('tenant.accounting.expenses');
        Route::get('/accounting/expenses/download', [TenantAccountingController::class, 'downloadExpenses'])->name('tenant.accounting.expenses.download');
        Route::post('/accounting/expenses', [TenantAccountingController::class, 'storeExpense'])->name('tenant.accounting.expenses.store');

        Route::get('/settings/shop', [TenantShopSettingController::class, 'edit'])->name('tenant.settings.shop.edit');
        Route::put('/settings/shop', [TenantShopSettingController::class, 'update'])->name('tenant.settings.shop.update');

        Route::get('/pos', [TenantSalesController::class, 'pos'])->name('tenant.pos.create');
        Route::post('/pos', [TenantSalesController::class, 'storeInvoice'])->name('tenant.pos.store');

        Route::get('/invoices', [TenantSalesController::class, 'invoices'])->name('tenant.invoices.index');
        Route::get('/invoices/{invoiceId}/pdf', [TenantSalesController::class, 'downloadInvoicePdf'])->name('tenant.invoices.pdf');
        Route::get('/invoices/{invoiceId}', [TenantSalesController::class, 'showInvoice'])->name('tenant.invoices.show');
        Route::post('/invoices/{invoiceId}/payments', [TenantSalesController::class, 'storePayment'])->name('tenant.invoices.payments.store');

        Route::get('/categories', [TenantCategoryController::class, 'index'])->name('tenant.categories.index');
        Route::post('/categories', [TenantCategoryController::class, 'store'])->name('tenant.categories.store');
        Route::put('/categories/{categoryId}', [TenantCategoryController::class, 'update'])->name('tenant.categories.update');
        Route::delete('/categories/{categoryId}', [TenantCategoryController::class, 'destroy'])->name('tenant.categories.destroy');

        Route::get('/brands', [TenantBrandController::class, 'index'])->name('tenant.brands.index');
        Route::post('/brands', [TenantBrandController::class, 'store'])->name('tenant.brands.store');
        Route::put('/brands/{brandId}', [TenantBrandController::class, 'update'])->name('tenant.brands.update');
        Route::delete('/brands/{brandId}', [TenantBrandController::class, 'destroy'])->name('tenant.brands.destroy');

        Route::get('/products', [TenantProductController::class, 'index'])->name('tenant.products.index');
        Route::get('/products/create', [TenantProductController::class, 'create'])->name('tenant.products.create');
        Route::post('/products', [TenantProductController::class, 'store'])->name('tenant.products.store');
        Route::get('/products/{productId}/edit', [TenantProductController::class, 'edit'])->name('tenant.products.edit');
        Route::put('/products/{productId}', [TenantProductController::class, 'update'])->name('tenant.products.update');
        Route::delete('/products/{productId}', [TenantProductController::class, 'destroy'])->name('tenant.products.destroy');

        Route::get('/warehouse-tasks', [TenantStockTransferController::class, 'warehouseTasks'])->name('tenant.stock-transfers.warehouse-tasks');
        Route::get('/shop-tasks', [TenantStockTransferController::class, 'shopTasks'])->name('tenant.stock-transfers.shop-tasks');

        Route::get('/stock-transfers', [TenantStockTransferController::class, 'index'])->name('tenant.stock-transfers.index');
        Route::get('/stock-transfers/create', [TenantStockTransferController::class, 'create'])->name('tenant.stock-transfers.create');
        Route::post('/stock-transfers', [TenantStockTransferController::class, 'store'])->name('tenant.stock-transfers.store');
        Route::get('/stock-transfers/{transferId}', [TenantStockTransferController::class, 'show'])->name('tenant.stock-transfers.show');
        Route::post('/stock-transfers/{transferId}/accept', [TenantStockTransferController::class, 'accept'])->name('tenant.stock-transfers.accept');
        Route::post('/stock-transfers/{transferId}/send', [TenantStockTransferController::class, 'send'])->name('tenant.stock-transfers.send');
        Route::post('/stock-transfers/{transferId}/receive', [TenantStockTransferController::class, 'receive'])->name('tenant.stock-transfers.receive');
        Route::post('/stock-transfers/{transferId}/reject', [TenantStockTransferController::class, 'reject'])->name('tenant.stock-transfers.reject');
        Route::post('/stock-transfers/{transferId}/acknowledge-received', [TenantStockTransferController::class, 'acknowledgeReceived'])->name('tenant.stock-transfers.acknowledge-received');
        Route::get('/stock', [TenantInventoryController::class, 'stockIndex'])->name('tenant.stock.index');
        Route::get('/stock-in', [TenantInventoryController::class, 'stockInCreate'])->name('tenant.stock-in.create');
        Route::post('/stock-in', [TenantInventoryController::class, 'stockInStore'])->name('tenant.stock-in.store');
        Route::get('/stock-adjustments', [TenantInventoryController::class, 'adjustmentCreate'])->name('tenant.stock-adjustments.create');
        Route::post('/stock-adjustments', [TenantInventoryController::class, 'adjustmentStore'])->name('tenant.stock-adjustments.store');
        Route::get('/stock-movements', [TenantInventoryController::class, 'movementIndex'])->name('tenant.stock-movements.index');

        Route::get('/customers', [TenantCustomerController::class, 'index'])->name('tenant.customers.index');
        Route::get('/customers/create', [TenantCustomerController::class, 'create'])->name('tenant.customers.create');
        Route::post('/customers', [TenantCustomerController::class, 'store'])->name('tenant.customers.store');
        Route::get('/customers/{customerId}', [TenantCustomerController::class, 'show'])->name('tenant.customers.show');
        Route::get('/customers/{customerId}/edit', [TenantCustomerController::class, 'edit'])->name('tenant.customers.edit');
        Route::put('/customers/{customerId}', [TenantCustomerController::class, 'update'])->name('tenant.customers.update');
        Route::delete('/customers/{customerId}', [TenantCustomerController::class, 'destroy'])->name('tenant.customers.destroy');
        Route::post('/customers/{customerId}/notes', [TenantCustomerController::class, 'storeNote'])->name('tenant.customers.notes.store');
    });
});
