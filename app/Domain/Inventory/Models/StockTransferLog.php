<?php

namespace App\Domain\Inventory\Models;

use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferLog extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'stock_transfer_id',
        'action',
        'from_status',
        'to_status',
        'user_id',
        'notes',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
