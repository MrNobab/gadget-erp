<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\Warehouse;

class StockTransferTaskSummary
{
    public static function empty(): array
    {
        return [
            'warehouse' => [
                'requested' => 0,
                'accepted' => 0,
                'in_transit' => 0,
                'received_unacknowledged' => 0,
                'total' => 0,
            ],
            'shop' => [
                'incoming' => 0,
                'waiting' => 0,
                'total' => 0,
            ],
            'total' => 0,
        ];
    }

    public function summary(): array
    {
        $warehouse = [
            'requested' => $this->countWarehouseTransfers(StockTransfer::STATUS_REQUESTED),
            'accepted' => $this->countWarehouseTransfers(StockTransfer::STATUS_ACCEPTED),
            'in_transit' => $this->countWarehouseTransfers(StockTransfer::STATUS_IN_TRANSIT),
            'received_unacknowledged' => $this->countWarehouseReceivedUnacknowledged(),
        ];

        $warehouse['total'] = array_sum($warehouse);

        $shop = [
            'incoming' => $this->countShopIncomingTransfers(),
            'waiting' => $this->countShopWaitingTransfers(),
        ];

        $shop['total'] = array_sum($shop);

        return [
            'warehouse' => $warehouse,
            'shop' => $shop,
            'total' => $warehouse['total'] + $shop['total'],
        ];
    }

    private function countWarehouseTransfers(string $status): int
    {
        return StockTransfer::query()
            ->whereHas('source', fn ($query) => $query->where('type', Warehouse::TYPE_WAREHOUSE))
            ->where('status', $status)
            ->count();
    }

    private function countWarehouseReceivedUnacknowledged(): int
    {
        return StockTransfer::query()
            ->whereHas('source', fn ($query) => $query->where('type', Warehouse::TYPE_WAREHOUSE))
            ->where('status', StockTransfer::STATUS_RECEIVED)
            ->whereNull('warehouse_acknowledged_at')
            ->count();
    }

    private function countShopIncomingTransfers(): int
    {
        return StockTransfer::query()
            ->whereHas('destination', fn ($query) => $query->where('type', Warehouse::TYPE_SHOP))
            ->where('status', StockTransfer::STATUS_IN_TRANSIT)
            ->count();
    }

    private function countShopWaitingTransfers(): int
    {
        return StockTransfer::query()
            ->whereHas('destination', fn ($query) => $query->where('type', Warehouse::TYPE_SHOP))
            ->whereIn('status', [
                StockTransfer::STATUS_REQUESTED,
                StockTransfer::STATUS_ACCEPTED,
            ])
            ->count();
    }
}
