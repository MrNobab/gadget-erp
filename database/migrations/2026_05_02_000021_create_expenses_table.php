<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->date('expense_date');
            $table->string('category');
            $table->decimal('amount', 16, 4);
            $table->string('payment_method')->default('cash');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
