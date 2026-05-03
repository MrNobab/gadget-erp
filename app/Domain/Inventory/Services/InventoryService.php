<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\ProductPurchaseLot;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function stockIn(array $data, int $createdBy): ProductPurchaseLot
    {
        $quantity = (int) $data['quantity'];
        $unitCost = (float) $data['unit_cost'];

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock in quantity must be greater than zero.');
        }

        if ($unitCost < 0) {
            throw new InvalidArgumentException('Unit cost cannot be negative.');
        }

        return DB::transaction(function () use ($data, $createdBy, $quantity, $unitCost): ProductPurchaseLot {
            Product::query()->findOrFail((int) $data['product_id']);
            Warehouse::query()->findOrFail((int) $data['warehouse_id']);
            $supplier = ! empty($data['supplier_id'])
                ? Supplier::query()->whereKey((int) $data['supplier_id'])->lockForUpdate()->firstOrFail()
                : null;

            $stock = $this->getOrCreateStock(
                (int) $data['warehouse_id'],
                (int) $data['product_id']
            );

            $beforeQty = (int) $stock->quantity;
            $beforeAverageCost = (float) $stock->average_cost_price;
            $beforeStockValue = (float) $stock->stock_value;

            if ($beforeStockValue <= 0 && $beforeQty > 0 && $beforeAverageCost > 0) {
                $beforeStockValue = round($beforeQty * $beforeAverageCost, 4);
            }

            $incomingValue = round($quantity * $unitCost, 4);
            $afterQty = $beforeQty + $quantity;
            $afterStockValue = round($beforeStockValue + $incomingValue, 4);
            $afterAverageCost = $afterQty > 0
                ? round($afterStockValue / $afterQty, 4)
                : 0;

            $lot = ProductPurchaseLot::query()->create([
                'warehouse_id' => (int) $data['warehouse_id'],
                'product_id' => (int) $data['product_id'],
                'supplier_id' => $supplier?->id,
                'quantity_purchased' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $incomingValue,
                'supplier_name' => $supplier?->name ?? ($data['supplier_name'] ?? null),
                'reference_no' => $data['reference_no'] ?? null,
                'purchased_at' => $data['purchased_at'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            $stock->update([
                'quantity' => $afterQty,
                'average_cost_price' => $afterAverageCost,
                'stock_value' => $afterStockValue,
            ]);

            StockMovement::query()->create([
                'warehouse_id' => (int) $data['warehouse_id'],
                'product_id' => (int) $data['product_id'],
                'type' => StockMovement::TYPE_IN,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'before_average_cost' => $beforeAverageCost,
                'after_average_cost' => $afterAverageCost,
                'reference_type' => 'purchase_lot',
                'reference_id' => $lot->id,
                'reason' => 'stock_in',
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            if ($supplier) {
                $supplier->increment('total_purchases', $incomingValue);
                $supplier->increment('total_due', $incomingValue);
            }

            return $lot;
        });
    }

    public function adjustStock(array $data, int $createdBy): WarehouseStock
    {
        $adjustmentQuantity = (int) $data['adjustment_quantity'];

        if ($adjustmentQuantity === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return DB::transaction(function () use ($data, $createdBy, $adjustmentQuantity): WarehouseStock {
            $product = Product::query()->findOrFail((int) $data['product_id']);
            Warehouse::query()->findOrFail((int) $data['warehouse_id']);

            $stock = $this->getOrCreateStock(
                (int) $data['warehouse_id'],
                (int) $data['product_id']
            );

            $beforeQty = (int) $stock->quantity;
            $beforeAverageCost = (float) $stock->average_cost_price;
            $beforeStockValue = (float) $stock->stock_value;

            if ($beforeStockValue <= 0 && $beforeQty > 0 && $beforeAverageCost > 0) {
                $beforeStockValue = round($beforeQty * $beforeAverageCost, 4);
            }

            $afterQty = $beforeQty + $adjustmentQuantity;

            if ($afterQty < 0) {
                throw new InvalidArgumentException('Stock cannot become negative.');
            }

            if ($adjustmentQuantity > 0) {
                $unitCost = isset($data['unit_cost']) && $data['unit_cost'] !== null && $data['unit_cost'] !== ''
                    ? (float) $data['unit_cost']
                    : ($beforeAverageCost > 0 ? $beforeAverageCost : (float) $product->cost_price);

                $incomingValue = round($adjustmentQuantity * $unitCost, 4);
                $afterStockValue = round($beforeStockValue + $incomingValue, 4);
                $afterAverageCost = $afterQty > 0 ? round($afterStockValue / $afterQty, 4) : 0;
            } else {
                $unitCost = $beforeAverageCost;
                $removedQuantity = abs($adjustmentQuantity);
                $removedValue = round($removedQuantity * $beforeAverageCost, 4);
                $afterStockValue = max(0, round($beforeStockValue - $removedValue, 4));
                $afterAverageCost = $afterQty > 0 ? $beforeAverageCost : 0;
            }

            $stock->update([
                'quantity' => $afterQty,
                'average_cost_price' => $afterAverageCost,
                'stock_value' => $afterStockValue,
            ]);

            StockMovement::query()->create([
                'warehouse_id' => (int) $data['warehouse_id'],
                'product_id' => (int) $data['product_id'],
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $adjustmentQuantity,
                'unit_cost' => $unitCost,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'before_average_cost' => $beforeAverageCost,
                'after_average_cost' => $afterAverageCost,
                'reference_type' => 'stock_adjustment',
                'reference_id' => null,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            return $stock;
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

        return WarehouseStock::query()
            ->whereKey($stock->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
