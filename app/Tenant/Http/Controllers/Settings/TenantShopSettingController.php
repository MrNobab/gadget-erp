<?php

namespace App\Tenant\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantShopSettingController extends Controller
{
    public function edit(Tenant $tenant): View
    {
        return view('tenant.settings.shop', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'max:150'],
            'shop_phone' => ['nullable', 'string', 'max:50'],
            'shop_email' => ['nullable', 'email', 'max:150'],
            'shop_address' => ['nullable', 'string', 'max:1000'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_position' => ['required', 'in:before,after'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $settings = $tenant->settings ?? [];

        if ($request->boolean('remove_logo') && ! empty($settings['logo_path'])) {
            Storage::disk('public')->delete($settings['logo_path']);
            unset($settings['logo_path']);
        }

        if ($request->hasFile('logo')) {
            if (! empty($settings['logo_path'])) {
                Storage::disk('public')->delete($settings['logo_path']);
            }

            $settings['logo_path'] = $request->file('logo')->store('tenant-logos', 'public');
        }

        $settings['shop_phone'] = $validated['shop_phone'] ?? null;
        $settings['shop_email'] = $validated['shop_email'] ?? null;
        $settings['shop_address'] = $validated['shop_address'] ?? null;
        $settings['invoice_prefix'] = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $validated['invoice_prefix']));
        $settings['currency_code'] = strtoupper($validated['currency_code']);
        $settings['currency_symbol'] = $validated['currency_symbol'];
        $settings['currency_position'] = $validated['currency_position'];
        $settings['tax_percent'] = round((float) $validated['tax_percent'], 2);
        $settings['invoice_footer'] = $validated['invoice_footer'] ?? null;

        $tenant->update([
            'name' => $validated['shop_name'],
            'settings' => $settings,
        ]);

        return redirect()
            ->route('tenant.settings.shop.edit', $tenant)
            ->with('success', 'Shop settings updated successfully.');
    }

    public function logo(Tenant $tenant)
    {
        $settings = $tenant->settings ?? [];
        $logoPath = $settings['logo_path'] ?? null;

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            abort(404, 'Shop logo not found.');
        }

        return response()->file(Storage::disk('public')->path($logoPath), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function settingsWithDefaults(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];

        return array_merge([
            'shop_phone' => $tenant->owner_phone,
            'shop_email' => $tenant->owner_email,
            'shop_address' => '',
            'invoice_prefix' => 'INV',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'currency_position' => 'before',
            'tax_percent' => 0,
            'invoice_footer' => 'Thank you for shopping with us.',
            'logo_path' => null,
        ], $settings);
    }
}
