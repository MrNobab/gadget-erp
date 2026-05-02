<?php

namespace App\Domain\Customers\Models;

use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNote extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'note',
        'created_by',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
