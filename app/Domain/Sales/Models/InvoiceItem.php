<?php

namespace App\Domain\Sales\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\Warehouse;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'warehouse_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'cost_price',
        'line_total',
        'gross_profit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'line_total' => 'decimal:4',
            'gross_profit' => 'decimal:4',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
