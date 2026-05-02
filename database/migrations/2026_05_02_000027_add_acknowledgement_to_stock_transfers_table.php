<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table): void {
            if (! Schema::hasColumn('stock_transfers', 'warehouse_acknowledged_by')) {
                $table->foreignId('warehouse_acknowledged_by')
                    ->nullable()
                    ->after('cancelled_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transfers', 'warehouse_acknowledged_at')) {
                $table->timestamp('warehouse_acknowledged_at')
                    ->nullable()
                    ->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_transfers', 'warehouse_acknowledged_by')) {
                $table->dropConstrainedForeignId('warehouse_acknowledged_by');
            }

            if (Schema::hasColumn('stock_transfers', 'warehouse_acknowledged_at')) {
                $table->dropColumn('warehouse_acknowledged_at');
            }
        });
    }
};
