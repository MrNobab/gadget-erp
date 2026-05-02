<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Platform\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,yearly,lifetime'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_products' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['slug'] ?: $validated['name']);

        Plan::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'price' => $validated['price'],
            'billing_cycle' => $validated['billing_cycle'],
            'max_users' => $validated['max_users'],
            'max_products' => $validated['max_products'],
            'features' => [
                'pos' => true,
                'inventory' => true,
                'customers' => true,
                'ledger' => true,
                'warranty' => true,
            ],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }
}
