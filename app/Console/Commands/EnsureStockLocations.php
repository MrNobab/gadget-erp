<?php

namespace App\Console\Commands;

use App\Domain\Inventory\Models\Warehouse;
use App\Platform\Models\Tenant;
use Illuminate\Console\Command;

class EnsureStockLocations extends Command
{
    protected $signature = 'nxpbd:ensure-stock-locations';

    protected $description = 'Create default warehouse and shop stock locations for each tenant';

    public function handle(): int
    {
        $created = 0;
        $updated = 0;

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use (&$created, &$updated): void {
            $mainWarehouse = Warehouse::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('name', 'Main Warehouse')
                ->first();

            if ($mainWarehouse) {
                if ($mainWarehouse->type !== Warehouse::TYPE_WAREHOUSE) {
                    $mainWarehouse->update(['type' => Warehouse::TYPE_WAREHOUSE]);
                    $updated++;
                }
            } else {
                Warehouse::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'name' => 'Main Warehouse',
                    'location' => 'Central warehouse',
                    'type' => Warehouse::TYPE_WAREHOUSE,
                    'is_default' => false,
                    'is_active' => true,
                ]);

                $created++;
            }

            $retailShop = Warehouse::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('name', 'Retail Shop')
                ->first();

            if (! $retailShop) {
                Warehouse::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'name' => 'Retail Shop',
                    'location' => 'Shop sales counter',
                    'type' => Warehouse::TYPE_SHOP,
                    'is_default' => true,
                    'is_active' => true,
                ]);

                $created++;
            }
        });

        $this->info("Locations created: {$created}");
        $this->info("Locations updated: {$updated}");

        return self::SUCCESS;
    }
}
