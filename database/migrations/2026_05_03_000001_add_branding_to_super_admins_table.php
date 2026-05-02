<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('super_admins', function (Blueprint $table): void {
            if (! Schema::hasColumn('super_admins', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('email');
            }

            if (! Schema::hasColumn('super_admins', 'brand_tagline')) {
                $table->string('brand_tagline')->nullable()->after('brand_name');
            }

            if (! Schema::hasColumn('super_admins', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('brand_tagline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('super_admins', function (Blueprint $table): void {
            if (Schema::hasColumn('super_admins', 'logo_path')) {
                $table->dropColumn('logo_path');
            }

            if (Schema::hasColumn('super_admins', 'brand_tagline')) {
                $table->dropColumn('brand_tagline');
            }

            if (Schema::hasColumn('super_admins', 'brand_name')) {
                $table->dropColumn('brand_name');
            }
        });
    }
};
