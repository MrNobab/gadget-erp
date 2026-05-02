<?php

namespace App\Providers;

use App\Domain\Inventory\Services\StockTransferTaskSummary;
use App\Platform\Models\SuperAdmin;
use App\Support\Services\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
            $view->with('platformBrand', $this->platformBrand());
        });
    }

    private function platformBrand(): array
    {
        $appName = (string) config('app.name', 'Gadget ERP');

        $brand = [
            'name' => $appName !== 'Laravel' ? $appName : 'Gadget ERP',
            'tagline' => 'NexproBD Retail SaaS',
            'logo_url' => null,
        ];

        try {
            if (
                ! Schema::hasTable('super_admins')
                || ! Schema::hasColumn('super_admins', 'brand_name')
                || ! Schema::hasColumn('super_admins', 'brand_tagline')
                || ! Schema::hasColumn('super_admins', 'logo_path')
            ) {
                return $brand;
            }

            $superAdmin = SuperAdmin::query()
                ->whereNotNull('brand_name')
                ->orWhereNotNull('brand_tagline')
                ->orWhereNotNull('logo_path')
                ->latest('updated_at')
                ->first();

            if (! $superAdmin) {
                return $brand;
            }

            $brand['name'] = $superAdmin->brand_name ?: $brand['name'];
            $brand['tagline'] = $superAdmin->brand_tagline ?: $brand['tagline'];

            if ($superAdmin->logo_path) {
                $brand['logo_url'] = route('admin.branding.logo') . '?v=' . ($superAdmin->updated_at?->timestamp ?? time());
            }
        } catch (Throwable) {
            return $brand;
        }

        return $brand;
    }
}
