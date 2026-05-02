<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            if (! Schema::hasColumn('warehouses', 'type')) {
                $table->string('type')->default('warehouse')->after('location');
                $table->index(['tenant_id', 'type']);
            }
        });

        DB::table('warehouses')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => 'warehouse']);
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            if (Schema::hasColumn('warehouses', 'type')) {
                $table->dropIndex(['tenant_id', 'type']);
                $table->dropColumn('type');
            }
        });
    }
};
