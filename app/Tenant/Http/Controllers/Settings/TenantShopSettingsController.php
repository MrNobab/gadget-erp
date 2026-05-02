<?php

namespace App\Tenant\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantShopSettingsController extends Controller
{
    public function edit(Tenant $tenant): View
    {
        $settings = $tenant->settings ?? [];

        return view('tenant.settings.edit', [
            'tenant' => $tenant,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'shop_address' => ['nullable', 'string', 'max:1000'],
            'shop_phone' => ['nullable', 'string', 'max:50'],
            'shop_email' => ['nullable', 'email', 'max:150'],

            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],

            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_position' => ['required', 'in:before,after'],

            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_footer_note' => ['nullable', 'string', 'max:1000'],

            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:512'],
        ]);

        $settings = $tenant->settings ?? [];

        if ($request->hasFile('logo')) {
            if (! empty($settings['logo_path'])) {
                Storage::disk('public')->delete($settings['logo_path']);
            }

            $validated['logo_path'] = $request->file('logo')->store(
                'tenant-logos/' . $tenant->id,
                'public'
            );
        } else {
            $validated['logo_path'] = $settings['logo_path'] ?? null;
        }

        unset($validated['logo']);

        $tenant->update([
            'settings' => array_merge($settings, $validated),
        ]);

        return redirect()
            ->route('tenant.settings.edit', $tenant)
            ->with('success', 'Shop settings updated successfully.');
    }
}
