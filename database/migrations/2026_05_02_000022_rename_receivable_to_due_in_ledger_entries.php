<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ledger_entries')) {
            DB::table('ledger_entries')
                ->where('account_type', 'receivable')
                ->update(['account_type' => 'due']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ledger_entries')) {
            DB::table('ledger_entries')
                ->where('account_type', 'due')
                ->update(['account_type' => 'receivable']);
        }
    }
};
