<?php

namespace App\Providers;

use App\Domain\Inventory\Services\StockTransferTaskSummary;
use App\Support\Services\TenantContext;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.tenant', function ($view): void {
            $summary = StockTransferTaskSummary::empty();

            if (session()->has('tenant_user_id') && TenantContext::has()) {
                $summary = app(StockTransferTaskSummary::class)->summary();
            }

            $view->with('transferTaskSummary', $summary);
        });
    }
}
