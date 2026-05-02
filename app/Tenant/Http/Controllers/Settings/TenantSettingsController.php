<?php

namespace App\Tenant\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantSettingsController extends Controller
{
    public function edit(Tenant $tenant): View
    {
        return view('tenant.settings.edit', [
            'tenant' => $tenant,
            'settings' => $tenant->settings ?? [],
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_email' => ['nullable', 'email', 'max:150'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_position' => ['required', 'in:before,after'],
            'currency_decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'invoice_prefix' => ['required', 'string', 'max:10'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        $settings = $tenant->settings ?? [];

        if ($request->hasFile('logo')) {
            $logoDirectory = public_path('uploads/tenant-logos');

            if (! is_dir($logoDirectory)) {
                mkdir($logoDirectory, 0755, true);
            }

            $oldLogoPath = $settings['logo_path'] ?? null;

            if ($oldLogoPath && is_file(public_path($oldLogoPath))) {
                @unlink(public_path($oldLogoPath));
            }

            $extension = $request->file('logo')->extension();
            $fileName = $tenant->slug . '-' . Str::random(12) . '.' . $extension;

            $request->file('logo')->move($logoDirectory, $fileName);

            $settings['logo_path'] = 'uploads/tenant-logos/' . $fileName;
        }

        $settings['store_address'] = $validated['store_address'] ?? null;
        $settings['store_phone'] = $validated['store_phone'] ?? null;
        $settings['store_email'] = $validated['store_email'] ?? null;
        $settings['tax_percent'] = round((float) $validated['tax_percent'], 4);
        $settings['currency_code'] = strtoupper($validated['currency_code']);
        $settings['currency_symbol'] = $validated['currency_symbol'];
        $settings['currency_position'] = $validated['currency_position'];
        $settings['currency_decimals'] = (int) $validated['currency_decimals'];
        $settings['invoice_prefix'] = strtoupper($validated['invoice_prefix']);
        $settings['invoice_footer'] = $validated['invoice_footer'] ?? null;

        $tenant->update([
            'name' => $validated['store_name'],
            'settings' => $settings,
        ]);

        return redirect()
            ->route('tenant.settings.edit', $tenant)
            ->with('success', 'Shop settings updated successfully.');
    }
}
