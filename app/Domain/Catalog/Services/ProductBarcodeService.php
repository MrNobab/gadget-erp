<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;

class ProductBarcodeService
{
    public function ensure(Product $product): Product
    {
        if (filled($product->barcode)) {
            return $product;
        }

        $product->update([
            'barcode' => $this->generate($product),
        ]);

        return $product->fresh() ?? $product;
    }

    private function generate(Product $product): string
    {
        $preferred = '2' . str_pad((string) $product->id, 11, '0', STR_PAD_LEFT);

        if ($this->isAvailable($product, $preferred)) {
            return $preferred;
        }

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $candidate = '29' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);

            if ($this->isAvailable($product, $candidate)) {
                return $candidate;
            }
        }

        return '2' . now()->format('ymdHis') . str_pad((string) ($product->id % 1000), 3, '0', STR_PAD_LEFT);
    }

    private function isAvailable(Product $product, string $barcode): bool
    {
        return Product::query()
            ->whereKeyNot($product->id)
            ->where(function ($query) use ($barcode): void {
                $query->where('barcode', $barcode)
                    ->orWhere('sku', $barcode);
            })
            ->doesntExist();
    }
}
