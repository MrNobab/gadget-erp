<?php

namespace App\Domain\Inventory\Models;

use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends TenantModel
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'transfer_number',
        'source_warehouse_id',
        'destination_warehouse_id',
        'status',
        'request_note',
        'warehouse_note',
        'receive_note',
        'requested_by',
        'accepted_by',
        'sent_by',
        'received_by',
        'rejected_by',
        'cancelled_by',
        'warehouse_acknowledged_by',
        'requested_at',
        'accepted_at',
        'sent_at',
        'received_at',
        'rejected_at',
        'cancelled_at',
        'warehouse_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'warehouse_acknowledged_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StockTransferLog::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function warehouseAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_acknowledged_by');
    }
}
