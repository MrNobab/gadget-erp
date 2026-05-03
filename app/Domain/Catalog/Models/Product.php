<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Inventory\Models\ProductPurchaseLot;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'category_id',
        'brand_id',
        'name',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'sale_price',
        'low_stock_threshold',
        'warranty_duration_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'low_stock_threshold' => 'integer',
            'warranty_duration_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function warehouseStocks(): HasMany
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

    public function barcodeValue(): string
    {
        return trim((string) ($this->barcode ?: $this->sku));
    }
}
