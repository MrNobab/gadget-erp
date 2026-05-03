<?php

namespace App\Domain\Sales\Services;

use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Sales\Models\InvoiceItem;
use App\Domain\Sales\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AfterSalesService
{
    public function recordReturn(array $data, int $createdBy): SalesReturn
    {
        return DB::transaction(function () use ($data, $createdBy): SalesReturn {
            $invoiceItem = InvoiceItem::query()
                ->with(['invoice', 'product'])
                ->whereKey((int) $data['invoice_item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $data['quantity'];

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Return quantity must be greater than zero.');
            }

            $alreadyReturned = SalesReturn::query()
                ->where('invoice_item_id', $invoiceItem->id)
                ->where('status', SalesReturn::STATUS_COMPLETED)
                ->sum('quantity');

            if (($alreadyReturned + $quantity) > (int) $invoiceItem->quantity) {
                throw new InvalidArgumentException('Return quantity exceeds sold quantity.');
            }

            $stock = $this->getOrCreateStock($invoiceItem->warehouse_id, $invoiceItem->product_id);
            $beforeQty = (int) $stock->quantity;
            $beforeAverageCost = (float) $stock->average_cost_price;
            $beforeStockValue = (float) $stock->stock_value;
            $unitCost = (float) $invoiceItem->cost_price;

            $afterQty = $beforeQty + $quantity;
            $afterStockValue = round($beforeStockValue + ($quantity * $unitCost), 4);
            $afterAverageCost = $afterQty > 0 ? round($afterStockValue / $afterQty, 4) : 0;

            $return = SalesReturn::query()->create([
                'invoice_id' => $invoiceItem->invoice_id,
                'invoice_item_id' => $invoiceItem->id,
                'warehouse_id' => $invoiceItem->warehouse_id,
                'product_id' => $invoiceItem->product_id,
                'quantity' => $quantity,
                'refund_amount' => round((float) ($data['refund_amount'] ?? 0), 4),
                'status' => SalesReturn::STATUS_COMPLETED,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'returned_at' => $data['returned_at'],
                'created_by' => $createdBy,
            ]);

            $stock->update([
                'quantity' => $afterQty,
                'average_cost_price' => $afterAverageCost,
                'stock_value' => $afterStockValue,
            ]);

            StockMovement::query()->create([
                'warehouse_id' => $invoiceItem->warehouse_id,
                'product_id' => $invoiceItem->product_id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'before_average_cost' => $beforeAverageCost,
                'after_average_cost' => $afterAverageCost,
                'reference_type' => 'sales_return',
                'reference_id' => $return->id,
                'reason' => 'sales_return',
                'notes' => 'Returned from invoice ' . $invoiceItem->invoice->invoice_number,
                'created_by' => $createdBy,
            ]);

            return $return->load(['invoice.customer', 'product', 'warehouse', 'creator']);
        });
    }

    private function getOrCreateStock(int $warehouseId, int $productId): WarehouseStock
    {
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        $stock = WarehouseStock::query()->create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => 0,
            'average_cost_price' => 0,
            'stock_value' => 0,
        ]);

        return WarehouseStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
    }
}
