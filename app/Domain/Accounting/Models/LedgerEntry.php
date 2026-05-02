<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Customers\Models\Customer;
use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends TenantModel
{
    public const ACCOUNT_SALES = 'sales';
    public const ACCOUNT_DUE = 'due';
    public const ACCOUNT_CASH = 'cash';
    public const ACCOUNT_BANK = 'bank';
    public const ACCOUNT_MOBILE_MONEY = 'mobile_money';
    public const ACCOUNT_CARD = 'card';
    public const ACCOUNT_EXPENSE = 'expense';
    public const ACCOUNT_COST_OF_GOODS = 'cost_of_goods';
    public const ACCOUNT_INVENTORY_ASSET = 'inventory_asset';

    protected $fillable = [
        'tenant_id',
        'entry_date',
        'reference_type',
        'reference_id',
        'account_type',
        'debit',
        'credit',
        'balance',
        'customer_id',
        'created_by',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'balance' => 'decimal:4',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
