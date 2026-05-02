<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_purchase_lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->unsignedInteger('quantity_purchased');
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 16, 4);

            $table->string('supplier_name')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('purchased_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'purchased_at']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'reference_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchase_lots');
    }
};
