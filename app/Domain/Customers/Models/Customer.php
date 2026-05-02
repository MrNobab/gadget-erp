<?php

namespace App\Domain\Customers\Models;

use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'address',
        'city',
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

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
