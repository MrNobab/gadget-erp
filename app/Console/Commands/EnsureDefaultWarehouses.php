<?php

namespace App\Console\Commands;

use App\Domain\Inventory\Models\Warehouse;
use App\Platform\Models\Tenant;
use Illuminate\Console\Command;

class EnsureDefaultWarehouses extends Command
{
    protected $signature = 'nxpbd:ensure-default-warehouses';

    protected $description = 'Create a default warehouse for every tenant that does not have one';

    public function handle(): int
    {
        $created = 0;

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use (&$created): void {
            $hasDefaultWarehouse = Warehouse::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('is_default', true)
                ->exists();

            if ($hasDefaultWarehouse) {
                return;
            }

            Warehouse::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Warehouse',
                'location' => 'Default shop warehouse',
                'is_default' => true,
                'is_active' => true,
            ]);

            $created++;
        });

        $this->info("Default warehouses created: {$created}");

        return self::SUCCESS;
    }
}
