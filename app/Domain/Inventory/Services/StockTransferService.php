<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\StockTransferItem;
use App\Domain\Inventory\Models\StockTransferLog;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Support\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockTransferService
{
    public function requestTransfer(array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId): StockTransfer {
            if ((int) $data['source_warehouse_id'] === (int) $data['destination_warehouse_id']) {
                throw new InvalidArgumentException('Source and destination cannot be the same.');
            }

            Warehouse::query()->findOrFail((int) $data['source_warehouse_id']);
            Warehouse::query()->findOrFail((int) $data['destination_warehouse_id']);

            $items = $this->cleanItems($data['items'] ?? []);

            if (count($items) === 0) {
                throw new InvalidArgumentException('At least one product is required.');
            }

            $transfer = StockTransfer::query()->create([
                'transfer_number' => $this->nextTransferNumber(),
                'source_warehouse_id' => (int) $data['source_warehouse_id'],
                'destination_warehouse_id' => (int) $data['destination_warehouse_id'],
                'status' => StockTransfer::STATUS_REQUESTED,
                'request_note' => $data['request_note'] ?? null,
                'requested_by' => $userId,
                'requested_at' => now(),
            ]);

            foreach ($items as $item) {
                Product::query()->findOrFail($item['product_id']);

                StockTransferItem::query()->create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => 0,
                    'total_cost' => 0,
                ]);
            }

            $this->log($transfer, 'requested', null, StockTransfer::STATUS_REQUESTED, $userId, $data['request_note'] ?? null);

            return $transfer->load(['source', 'destination', 'items.product', 'requestedBy']);
        });
    }

    public function accept(StockTransfer $transfer, int $userId, ?string $note = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId, $note): StockTransfer {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== StockTransfer::STATUS_REQUESTED) {
                throw new InvalidArgumentException('Only requested transfers can be accepted.');
            }

            $from = $transfer->status;

            $transfer->update([
                'status' => StockTransfer::STATUS_ACCEPTED,
                'accepted_by' => $userId,
                'accepted_at' => now(),
                'warehouse_note' => $note,
            ]);

            $this->log($transfer, 'accepted', $from, StockTransfer::STATUS_ACCEPTED, $userId, $note);

            return $transfer->fresh(['source', 'destination', 'items.product', 'logs.user']);
        });
    }

    public function send(StockTransfer $transfer, int $userId, ?string $note = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId, $note): StockTransfer {
            $transfer = StockTransfer::query()
                ->with(['items.product'])
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StockTransfer::STATUS_ACCEPTED) {
                throw new InvalidArgumentException('Only accepted transfers can be sent.');
            }

            foreach ($transfer->items as $item) {
                $sourceStock = WarehouseStock::query()
                    ->where('warehouse_id', $transfer->source_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $sourceStock || (int) $sourceStock->quantity < (int) $item->quantity) {
                    throw new InvalidArgumentException('Insufficient source stock for ' . $item->product->name . '.');
                }
            }

            foreach ($transfer->items as $item) {
                $sourceStock = WarehouseStock::query()
                    ->where('warehouse_id', $transfer->source_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $beforeQty = (int) $sourceStock->quantity;
                $beforeAverageCost = (float) $sourceStock->average_cost_price;
                $beforeStockValue = (float) $sourceStock->stock_value;

                $unitCost = $beforeAverageCost > 0 ? $beforeAverageCost : (float) $item->product->cost_price;
                $quantity = (int) $item->quantity;
                $removedValue = round($quantity * $unitCost, 4);

                $afterQty = $beforeQty - $quantity;
                $afterStockValue = max(0, round($beforeStockValue - $removedValue, 4));
                $afterAverageCost = $afterQty > 0 ? $beforeAverageCost : 0;

                $sourceStock->update([
                    'quantity' => $afterQty,
                    'average_cost_price' => $afterAverageCost,
                    'stock_value' => $afterStockValue,
                ]);

                $item->update([
                    'unit_cost' => $unitCost,
                    'total_cost' => $removedValue,
                ]);

                StockMovement::query()->create([
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_out',
                    'quantity' => -1 * $quantity,
                    'unit_cost' => $unitCost,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'before_average_cost' => $beforeAverageCost,
                    'after_average_cost' => $afterAverageCost,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'reason' => 'transfer_sent',
                    'notes' => 'Sent via transfer ' . $transfer->transfer_number,
                    'created_by' => $userId,
                ]);
            }

            $from = $transfer->status;

            $transfer->update([
                'status' => StockTransfer::STATUS_IN_TRANSIT,
                'sent_by' => $userId,
                'sent_at' => now(),
                'warehouse_note' => $note ?: $transfer->warehouse_note,
            ]);

            $this->log($transfer, 'sent', $from, StockTransfer::STATUS_IN_TRANSIT, $userId, $note);

            return $transfer->fresh(['source', 'destination', 'items.product', 'logs.user']);
        });
    }

    public function receive(StockTransfer $transfer, int $userId, ?string $note = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId, $note): StockTransfer {
            $transfer = StockTransfer::query()
                ->with(['items.product'])
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status !== StockTransfer::STATUS_IN_TRANSIT) {
                throw new InvalidArgumentException('Only in-transit transfers can be received.');
            }

            foreach ($transfer->items as $item) {
                $destinationStock = $this->getOrCreateStock($transfer->destination_warehouse_id, $item->product_id);

                $beforeQty = (int) $destinationStock->quantity;
                $beforeAverageCost = (float) $destinationStock->average_cost_price;
                $beforeStockValue = (float) $destinationStock->stock_value;

                if ($beforeStockValue <= 0 && $beforeQty > 0 && $beforeAverageCost > 0) {
                    $beforeStockValue = round($beforeQty * $beforeAverageCost, 4);
                }

                $quantity = (int) $item->quantity;
                $unitCost = (float) $item->unit_cost;
                $incomingValue = round($quantity * $unitCost, 4);

                $afterQty = $beforeQty + $quantity;
                $afterStockValue = round($beforeStockValue + $incomingValue, 4);
                $afterAverageCost = $afterQty > 0 ? round($afterStockValue / $afterQty, 4) : 0;

                $destinationStock->update([
                    'quantity' => $afterQty,
                    'average_cost_price' => $afterAverageCost,
                    'stock_value' => $afterStockValue,
                ]);

                StockMovement::query()->create([
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'transfer_in',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'before_average_cost' => $beforeAverageCost,
                    'after_average_cost' => $afterAverageCost,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'reason' => 'transfer_received',
                    'notes' => 'Received via transfer ' . $transfer->transfer_number,
                    'created_by' => $userId,
                ]);
            }

            $from = $transfer->status;

            $transfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_by' => $userId,
                'received_at' => now(),
                'receive_note' => $note,
            ]);

            $this->log($transfer, 'received', $from, StockTransfer::STATUS_RECEIVED, $userId, $note);

            return $transfer->fresh(['source', 'destination', 'items.product', 'logs.user']);
        });
    }

    public function reject(StockTransfer $transfer, int $userId, ?string $note = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId, $note): StockTransfer {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== StockTransfer::STATUS_REQUESTED) {
                throw new InvalidArgumentException('Only requested transfers can be rejected.');
            }

            $from = $transfer->status;

            $transfer->update([
                'status' => StockTransfer::STATUS_REJECTED,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'warehouse_note' => $note,
            ]);

            $this->log($transfer, 'rejected', $from, StockTransfer::STATUS_REJECTED, $userId, $note);

            return $transfer->fresh(['source', 'destination', 'items.product', 'logs.user']);
        });
    }

    private function cleanItems(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $clean[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
            ];
        }

        return $clean;
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

    private function log(StockTransfer $transfer, string $action, ?string $fromStatus, ?string $toStatus, int $userId, ?string $notes = null): void
    {
        StockTransferLog::query()->create([
            'stock_transfer_id' => $transfer->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    private function nextTransferNumber(): string
    {
        $tenantId = TenantContext::id();
        $prefix = 'TRF-' . now()->format('Ymd') . '-';

        $last = StockTransfer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('transfer_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $next = 1;

        if ($last) {
            $next = ((int) str_replace($prefix, '', $last->transfer_number)) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
