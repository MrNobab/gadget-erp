<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPurchaseLot extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'quantity_purchased',
        'unit_cost',
        'total_cost',
        'supplier_name',
        'reference_no',
        'purchased_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_purchased' => 'integer',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'purchased_at' => 'date',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
