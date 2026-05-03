<?php

namespace App\Domain\Purchasing\Models;

use App\Domain\Inventory\Models\ProductPurchaseLot;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'address',
        'total_purchases',
        'total_paid',
        'total_due',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_purchases' => 'decimal:4',
            'total_paid' => 'decimal:4',
            'total_due' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseLots(): HasMany
    {
        return $this->hasMany(ProductPurchaseLot::class);
    }
}
