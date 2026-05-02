<?php

namespace App\Domain\Accounting\Models;

use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'expense_date',
        'category',
        'amount',
        'payment_method',
        'reference',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::updating(function (): bool {
            return false;
        });

        static::deleting(function (): bool {
            return false;
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
