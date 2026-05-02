<?php

use App\Platform\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignIdFor(Tenant::class)
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->boolean('is_owner')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_owner');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index(['tenant_id', 'is_active']);
            $table->index('is_owner');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropIndex(['is_owner']);

            $table->dropColumn([
                'tenant_id',
                'is_owner',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
