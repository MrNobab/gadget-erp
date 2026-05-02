<?php

namespace App\Domain\Sales\Services;

use App\Domain\Accounting\Services\LedgerService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\InvoiceItem;
use App\Domain\Sales\Models\Payment;
use App\Support\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesService
{
    public function createPostedInvoice(array $data, int $createdBy): Invoice
    {
        return DB::transaction(function () use ($data, $createdBy): Invoice {
            $warehouse = Warehouse::query()->findOrFail((int) $data['warehouse_id']);

            $customer = Customer::query()
                ->whereKey((int) $data['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $items = $this->cleanItems($data['items'] ?? []);

            if (count($items) === 0) {
                throw new InvalidArgumentException('At least one valid invoice item is required.');
            }

            $subtotal = 0;

            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 4);

            if ($discountAmount < 0) {
                throw new InvalidArgumentException('Discount cannot be negative.');
            }

            if ($discountAmount > $subtotal) {
                throw new InvalidArgumentException('Discount cannot be greater than subtotal.');
            }

            $taxPercent = round((float) ($data['tax_percent'] ?? 0), 4);
            $taxableAmount = max(0, $subtotal - $discountAmount);
            $taxAmount = round(($taxableAmount * $taxPercent) / 100, 4);
            $total = round($taxableAmount + $taxAmount, 4);

            $paidAmount = round((float) ($data['paid_amount'] ?? 0), 4);

            if ($paidAmount < 0) {
                throw new InvalidArgumentException('Paid amount cannot be negative.');
            }

            if ($paidAmount > $total) {
                throw new InvalidArgumentException('Paid amount cannot be greater than invoice total.');
            }

            $previousDue = round((float) $customer->total_due, 4);
            $dueAmount = round($total - $paidAmount, 4);
            $paymentStatus = $this->paymentStatus($total, $paidAmount);

            $invoice = Invoice::query()->create([
                'warehouse_id' => $warehouse->id,
                'customer_id' => $customer->id,
                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => $data['invoice_date'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'previous_due' => $previousDue,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => Invoice::STATUS_POSTED,
                'payment_status' => $paymentStatus,
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
                'posted_at' => now(),
            ]);

            foreach ($items as $item) {
                $this->createInvoiceItemAndDeductStock($invoice, $warehouse->id, $item, $createdBy);
            }

            app(LedgerService::class)->recordInvoicePosted(
                $invoice->fresh(['items', 'customer']),
                $createdBy
            );

            if ($paidAmount > 0) {
                $payment = Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount' => $paidAmount,
                    'method' => $data['payment_method'] ?? 'cash',
                    'reference' => $data['payment_reference'] ?? null,
                    'paid_at' => $data['invoice_date'],
                    'notes' => 'Initial invoice payment',
                    'created_by' => $createdBy,
                ]);

                app(LedgerService::class)->recordPaymentReceived($payment, $createdBy);
            }

            $customer->increment('total_purchases', $total);
            $customer->increment('total_paid', $paidAmount);
            $customer->increment('total_due', $dueAmount);

            return $invoice->load(['customer', 'warehouse', 'items.product', 'payments']);
        });
    }

    public function recordPayment(Invoice $invoice, array $data, int $createdBy): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $createdBy): Invoice {
            $invoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($invoice->status !== Invoice::STATUS_POSTED) {
                throw new InvalidArgumentException('Only posted invoices can receive payments.');
            }

            $amount = round((float) $data['amount'], 4);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            if ($amount > (float) $invoice->due_amount) {
                throw new InvalidArgumentException('Payment amount cannot be greater than invoice due.');
            }

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? 'Due collected from invoice dues page',
                'created_by' => $createdBy,
            ]);

            app(LedgerService::class)->recordPaymentReceived($payment, $createdBy);

            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 4);
            $newDueAmount = max(0, round((float) $invoice->total - $newPaidAmount, 4));

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $newDueAmount,
                'payment_status' => $this->paymentStatus((float) $invoice->total, $newPaidAmount),
            ]);

            $invoice->customer->increment('total_paid', $amount);
            $invoice->customer->decrement('total_due', $amount);

            return $invoice->fresh(['customer', 'warehouse', 'items.product', 'payments.creator']);
        });
    }

    public function recordCustomerDuePayment(Customer $customer, array $data, int $createdBy): void
    {
        DB::transaction(function () use ($customer, $data, $createdBy): void {
            $amount = round((float) ($data['amount'] ?? 0), 4);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $customer = Customer::query()
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $dueInvoices = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('status', Invoice::STATUS_POSTED)
                ->where('due_amount', '>', 0)
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $totalInvoiceDue = round((float) $dueInvoices->sum('due_amount'), 4);

            if ($totalInvoiceDue <= 0) {
                throw new InvalidArgumentException('This customer has no invoice due.');
            }

            if ($amount > $totalInvoiceDue) {
                throw new InvalidArgumentException('Payment amount cannot be greater than total invoice due.');
            }

            $remainingAmount = $amount;

            foreach ($dueInvoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $invoiceDue = round((float) $invoice->due_amount, 4);
                $paymentAmount = min($remainingAmount, $invoiceDue);

                $payment = Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount' => $paymentAmount,
                    'method' => $data['method'],
                    'reference' => $data['reference'] ?? null,
                    'paid_at' => $data['paid_at'],
                    'notes' => $data['notes'] ?? 'Due collected from customer ledger page',
                    'created_by' => $createdBy,
                ]);

                app(LedgerService::class)->recordPaymentReceived($payment, $createdBy);

                $newPaidAmount = round((float) $invoice->paid_amount + $paymentAmount, 4);
                $newDueAmount = max(0, round((float) $invoice->total - $newPaidAmount, 4));

                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'due_amount' => $newDueAmount,
                    'payment_status' => $this->paymentStatus((float) $invoice->total, $newPaidAmount),
                ]);

                $remainingAmount = round($remainingAmount - $paymentAmount, 4);
            }

            $customer->increment('total_paid', $amount);
            $customer->decrement('total_due', $amount);
        });
    }

    private function createInvoiceItemAndDeductStock(Invoice $invoice, int $warehouseId, array $item, int $createdBy): void
    {
        $product = Product::query()->findOrFail((int) $item['product_id']);

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if (! $stock || (int) $stock->quantity < $item['quantity']) {
            throw new InvalidArgumentException("Insufficient stock for {$product->name}.");
        }

        $beforeQty = (int) $stock->quantity;
        $beforeAverageCost = (float) $stock->average_cost_price;
        $beforeStockValue = (float) $stock->stock_value;

        if ($beforeAverageCost <= 0) {
            $beforeAverageCost = (float) $product->cost_price;
        }

        $quantity = (int) $item['quantity'];
        $unitPrice = round((float) $item['unit_price'], 4);
        $costPrice = round($beforeAverageCost, 4);
        $lineTotal = round($quantity * $unitPrice, 4);
        $grossProfit = round(($unitPrice - $costPrice) * $quantity, 4);

        $invoiceItem = InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'cost_price' => $costPrice,
            'line_total' => $lineTotal,
            'gross_profit' => $grossProfit,
        ]);

        $afterQty = $beforeQty - $quantity;
        $removedValue = round($quantity * $costPrice, 4);
        $afterStockValue = max(0, round($beforeStockValue - $removedValue, 4));
        $afterAverageCost = $afterQty > 0 ? $beforeAverageCost : 0;

        $stock->update([
            'quantity' => $afterQty,
            'average_cost_price' => $afterAverageCost,
            'stock_value' => $afterStockValue,
        ]);

        StockMovement::query()->create([
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => -1 * $quantity,
            'unit_cost' => $costPrice,
            'before_qty' => $beforeQty,
            'after_qty' => $afterQty,
            'before_average_cost' => $beforeAverageCost,
            'after_average_cost' => $afterAverageCost,
            'reference_type' => 'invoice_item',
            'reference_id' => $invoiceItem->id,
            'reason' => 'sale',
            'notes' => 'Sold via invoice ' . $invoice->invoice_number,
            'created_by' => $createdBy,
        ]);
    }

    private function cleanItems(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Unit price cannot be negative.');
            }

            $clean[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'unit_price' => round($unitPrice, 4),
            ];
        }

        return $clean;
    }

    private function paymentStatus(float $total, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return Invoice::PAYMENT_UNPAID;
        }

        if ($paidAmount >= $total) {
            return Invoice::PAYMENT_PAID;
        }

        return Invoice::PAYMENT_PARTIAL;
    }

    private function nextInvoiceNumber(): string
    {
        $tenant = TenantContext::get();
        $tenantId = TenantContext::id();
        $settings = $tenant?->settings ?? [];

        $rawPrefix = $settings['invoice_prefix'] ?? 'INV';
        $cleanPrefix = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $rawPrefix));
        $cleanPrefix = $cleanPrefix !== '' ? $cleanPrefix : 'INV';

        $prefix = $cleanPrefix . '-' . now()->format('Ymd') . '-';

        $lastInvoice = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $next = 1;

        if ($lastInvoice) {
            $lastNumber = (int) str_replace($prefix, '', $lastInvoice->invoice_number);
            $next = $lastNumber + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
