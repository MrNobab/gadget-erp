<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('sku');
                $table->unique(['tenant_id', 'barcode'], 'products_tenant_barcode_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropUnique('products_tenant_barcode_unique');
                $table->dropColumn('barcode');
            }
        });
    }
};
