<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Expense;
use App\Domain\Accounting\Models\LedgerEntry;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function recordInvoicePosted(Invoice $invoice, int $createdBy): void
    {
        DB::transaction(function () use ($invoice, $createdBy): void {
            $exists = LedgerEntry::query()
                ->where('reference_type', 'invoice')
                ->where('reference_id', $invoice->id)
                ->where('account_type', LedgerEntry::ACCOUNT_SALES)
                ->exists();

            if ($exists) {
                return;
            }

            $invoice->loadMissing(['items', 'customer']);

            $total = round((float) $invoice->total, 4);
            $costOfGoods = round((float) $invoice->items->sum(function ($item): float {
                return (float) $item->cost_price * (int) $item->quantity;
            }), 4);

            $this->append([
                'entry_date' => $invoice->invoice_date,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'account_type' => LedgerEntry::ACCOUNT_DUE,
                'debit' => $total,
                'credit' => 0,
                'customer_id' => $invoice->customer_id,
                'created_by' => $createdBy,
                'notes' => 'Due created from invoice: ' . $invoice->invoice_number,
            ]);

            $this->append([
                'entry_date' => $invoice->invoice_date,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'account_type' => LedgerEntry::ACCOUNT_SALES,
                'debit' => 0,
                'credit' => $total,
                'customer_id' => $invoice->customer_id,
                'created_by' => $createdBy,
                'notes' => 'Sales revenue: ' . $invoice->invoice_number,
            ]);

            if ($costOfGoods > 0) {
                $this->append([
                    'entry_date' => $invoice->invoice_date,
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'account_type' => LedgerEntry::ACCOUNT_COST_OF_GOODS,
                    'debit' => $costOfGoods,
                    'credit' => 0,
                    'customer_id' => $invoice->customer_id,
                    'created_by' => $createdBy,
                    'notes' => 'Cost of goods sold: ' . $invoice->invoice_number,
                ]);

                $this->append([
                    'entry_date' => $invoice->invoice_date,
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'account_type' => LedgerEntry::ACCOUNT_INVENTORY_ASSET,
                    'debit' => 0,
                    'credit' => $costOfGoods,
                    'customer_id' => $invoice->customer_id,
                    'created_by' => $createdBy,
                    'notes' => 'Inventory reduced by sale: ' . $invoice->invoice_number,
                ]);
            }
        });
    }

    public function recordPaymentReceived(Payment $payment, int $createdBy): void
    {
        DB::transaction(function () use ($payment, $createdBy): void {
            $exists = LedgerEntry::query()
                ->where('reference_type', 'payment')
                ->where('reference_id', $payment->id)
                ->where('account_type', LedgerEntry::ACCOUNT_DUE)
                ->exists();

            if ($exists) {
                return;
            }

            $payment->loadMissing(['invoice', 'customer']);

            $amount = round((float) $payment->amount, 4);
            $paymentAccount = $this->paymentAccount($payment->method);

            $this->append([
                'entry_date' => $payment->paid_at,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'account_type' => $paymentAccount,
                'debit' => $amount,
                'credit' => 0,
                'customer_id' => $payment->customer_id,
                'created_by' => $createdBy,
                'payment_method' => $payment->method,
                'notes' => 'Due collection for invoice: ' . ($payment->invoice?->invoice_number ?? '-'),
            ]);

            $this->append([
                'entry_date' => $payment->paid_at,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'account_type' => LedgerEntry::ACCOUNT_DUE,
                'debit' => 0,
                'credit' => $amount,
                'customer_id' => $payment->customer_id,
                'created_by' => $createdBy,
                'payment_method' => $payment->method,
                'notes' => 'Due reduced by collection: ' . ($payment->invoice?->invoice_number ?? '-'),
            ]);
        });
    }

    public function recordExpense(Expense $expense, int $createdBy): void
    {
        DB::transaction(function () use ($expense, $createdBy): void {
            $exists = LedgerEntry::query()
                ->where('reference_type', 'expense')
                ->where('reference_id', $expense->id)
                ->where('account_type', LedgerEntry::ACCOUNT_EXPENSE)
                ->exists();

            if ($exists) {
                return;
            }

            $amount = round((float) $expense->amount, 4);
            $paymentAccount = $this->paymentAccount($expense->payment_method);

            $this->append([
                'entry_date' => $expense->expense_date,
                'reference_type' => 'expense',
                'reference_id' => $expense->id,
                'account_type' => LedgerEntry::ACCOUNT_EXPENSE,
                'debit' => $amount,
                'credit' => 0,
                'created_by' => $createdBy,
                'payment_method' => $expense->payment_method,
                'notes' => 'Expense: ' . $expense->category,
            ]);

            $this->append([
                'entry_date' => $expense->expense_date,
                'reference_type' => 'expense',
                'reference_id' => $expense->id,
                'account_type' => $paymentAccount,
                'debit' => 0,
                'credit' => $amount,
                'created_by' => $createdBy,
                'payment_method' => $expense->payment_method,
                'notes' => 'Paid expense: ' . $expense->category,
            ]);
        });
    }

    public function append(array $data): LedgerEntry
    {
        $accountType = $data['account_type'];

        $previousBalance = (float) optional(
            LedgerEntry::query()
                ->where('account_type', $accountType)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first()
        )->balance;

        $debit = round((float) ($data['debit'] ?? 0), 4);
        $credit = round((float) ($data['credit'] ?? 0), 4);
        $balance = round($previousBalance + $debit - $credit, 4);

        $data['debit'] = $debit;
        $data['credit'] = $credit;
        $data['balance'] = $balance;

        return LedgerEntry::query()->create($data);
    }

    private function paymentAccount(string $method): string
    {
        return match ($method) {
            'bank' => LedgerEntry::ACCOUNT_BANK,
            'mobile_money' => LedgerEntry::ACCOUNT_MOBILE_MONEY,
            'card' => LedgerEntry::ACCOUNT_CARD,
            default => LedgerEntry::ACCOUNT_CASH,
        };
    }
}
