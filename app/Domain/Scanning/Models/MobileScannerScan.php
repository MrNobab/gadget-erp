<?php

namespace App\Domain\Scanning\Models;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileScannerScan extends TenantModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONSUMED = 'consumed';

    protected $fillable = [
        'tenant_id',
        'mobile_scanner_session_id',
        'product_id',
        'code',
        'quantity',
        'status',
        'scanned_by',
        'scanned_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'scanned_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function scannerSession(): BelongsTo
    {
        return $this->belongsTo(MobileScannerSession::class, 'mobile_scanner_session_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
