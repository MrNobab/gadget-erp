<?php

namespace App\Domain\Inventory\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends TenantModel
{
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_SHOP = 'shop';

    protected $fillable = [
        'tenant_id',
        'name',
        'location',
        'type',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function purchaseLots(): HasMany
    {
        return $this->hasMany(ProductPurchaseLot::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'source_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'destination_warehouse_id');
    }
}
