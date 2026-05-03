<?php

namespace App\Platform\Services;

use App\Domain\Inventory\Models\Warehouse;
use App\Models\User;
use App\Platform\Models\License;
use App\Platform\Models\LicenseLog;
use App\Platform\Models\Plan;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantProvisioner
{
    public function createTenantWithOwner(array $data, int $superAdminId): Tenant
    {
        return DB::transaction(function () use ($data, $superAdminId): Tenant {
            $slug = Str::slug($data['slug'] ?: $data['name']);

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_phone' => $data['owner_phone'] ?? null,
                'status' => 'active',
                'settings' => [
                    'currency' => 'BDT',
                    'timezone' => 'Asia/Dhaka',
                    'invoice_prefix' => strtoupper(Str::limit($slug, 4, '')),
                ],
            ]);

            $plan = Plan::query()->findOrFail((int) $data['plan_id']);

            $license = License::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => License::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => $data['expires_at'],
            ]);

            LicenseLog::query()->create([
                'license_id' => $license->id,
                'changed_by_super_admin_id' => $superAdminId,
                'from_status' => null,
                'to_status' => License::STATUS_ACTIVE,
                'reason' => 'Tenant created with active license.',
            ]);

            User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
                'is_owner' => true,
                'role' => User::ROLE_OWNER,
                'is_active' => true,
            ]);

            Warehouse::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Warehouse',
                'location' => 'Default shop warehouse',
                'is_default' => true,
                'is_active' => true,
            ]);

            return $tenant->load(['license.plan']);
        });
    }
}
