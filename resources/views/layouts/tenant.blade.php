<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($tenant->name ?? 'Gadget ERP') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        <header class="bg-white border-b border-slate-200 shadow-sm">
            @php
                $brand = $platformBrand ?? ['name' => 'Gadget ERP', 'tagline' => 'NexproBD Retail SaaS', 'logo_url' => null];
                $tenantSettings = $tenant->settings ?? [];
                $shopLogoPath = $tenantSettings['logo_path'] ?? null;
                $shopLogoUrl = session()->has('tenant_user_id') && isset($tenant) && $shopLogoPath ? route('tenant.settings.shop.logo', $tenant) . '?v=' . ($tenant->updated_at?->timestamp ?? time()) : null;
                $shopName = $tenant->name ?? 'Gadget ERP';
                $profileName = (string) session('tenant_user_name', 'User');
                $profileEmail = (string) session('tenant_user_email', '');
                $shopParts = preg_split('/\s+/', trim($shopName)) ?: [];
                $shopInitials = strtoupper(substr($shopParts[0] ?? 'S', 0, 1) . substr($shopParts[1] ?? '', 0, 1));
                $brandParts = preg_split('/\s+/', trim((string) ($brand['name'] ?? 'Gadget ERP'))) ?: [];
                $brandInitials = strtoupper(substr($brandParts[0] ?? 'G', 0, 1) . substr($brandParts[1] ?? 'E', 0, 1));
            @endphp

            <div class="max-w-7xl mx-auto px-4 py-4 grid grid-cols-1 md:grid-cols-3 items-center gap-4">
                <a href="{{ isset($tenant) ? route('tenant.dashboard', $tenant) : '#' }}" class="flex items-center gap-3 justify-center md:justify-start">
                    <span class="h-11 w-11 rounded-lg bg-slate-900 text-white grid place-items-center overflow-hidden font-bold">
                        @if(!empty($brand['logo_url']))
                            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'ERP Owner' }}" class="h-full w-full object-contain bg-white">
                        @else
                            {{ $brandInitials }}
                        @endif
                    </span>

                    <span>
                        <span class="block text-sm font-bold text-slate-900">{{ $brand['name'] ?? 'Gadget ERP' }}</span>
                        <span class="block text-xs text-slate-500">{{ $brand['tagline'] ?? 'NexproBD Retail SaaS' }}</span>
                    </span>
                </a>

                <div class="flex items-center justify-center gap-3">
                    <span class="h-12 w-12 rounded-lg border border-slate-200 bg-slate-50 grid place-items-center overflow-hidden text-sm font-bold text-slate-700">
                        @if($shopLogoUrl)
                            <img src="{{ $shopLogoUrl }}" alt="{{ $shopName }} Logo" class="h-full w-full object-contain">
                        @else
                            {{ $shopInitials }}
                        @endif
                    </span>

                    <div class="text-center">
                        <h1 class="text-lg font-bold leading-tight">{{ $shopName }}</h1>
                    </div>
                </div>

                @if(session()->has('tenant_user_id') && isset($tenant))
                    @php
                        $notificationCount = (int) ($transferTaskSummary['total'] ?? 0);
                        $notificationBadge = $notificationCount > 99 ? '99+' : $notificationCount;
                    @endphp

                    <div class="flex items-center justify-center md:justify-end gap-2">
                        <a href="{{ route('tenant.notifications.index', $tenant) }}" class="relative h-10 w-10 rounded-lg {{ request()->routeIs('tenant.notifications.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} grid place-items-center" title="Notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                            </svg>

                            @if($notificationCount > 0)
                                <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-bold text-white">
                                    {{ $notificationBadge }}
                                </span>
                            @endif
                        </a>

                        <details class="relative" data-menu-dropdown>
                            <summary class="list-none cursor-pointer h-10 w-10 rounded-lg bg-slate-900 text-white grid place-items-center hover:bg-slate-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </summary>

                            <div class="absolute right-0 z-20 mt-2 w-56 rounded-lg border border-slate-200 bg-white shadow-lg overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <div class="text-sm font-semibold">{{ $profileName }}</div>
                                    <div class="text-xs text-slate-500 truncate">{{ $profileEmail }}</div>
                                </div>

                                <a href="{{ route('tenant.settings.shop.edit', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Settings
                                </a>

                                @if(in_array(session('tenant_user_role'), ['owner', 'manager', 'cashier'], true))
                                    <a href="{{ route('tenant.users.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Staff Users
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('tenant.logout', $tenant) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-red-700 hover:bg-red-50">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                @endif
            </div>

            @if(session()->has('tenant_user_id') && isset($tenant))
                <nav class="border-t border-slate-200">
                    @php
                        $warehouseTasks = $transferTaskSummary['warehouse'] ?? [];
                        $shopTasks = $transferTaskSummary['shop'] ?? [];

                        $warehouseTaskTotal = (int) ($warehouseTasks['total'] ?? 0);
                        $shopTaskTotal = (int) ($shopTasks['total'] ?? 0);

                        $warehouseTaskBadge = $warehouseTaskTotal > 99 ? '99+' : $warehouseTaskTotal;
                        $shopTaskBadge = $shopTaskTotal > 99 ? '99+' : $shopTaskTotal;

                        $warehouseTaskTitle = sprintf(
                            '%d new, %d to send, %d in transit, %d need acknowledgement',
                            (int) ($warehouseTasks['requested'] ?? 0),
                            (int) ($warehouseTasks['accepted'] ?? 0),
                            (int) ($warehouseTasks['in_transit'] ?? 0),
                            (int) ($warehouseTasks['received_unacknowledged'] ?? 0),
                        );

                        $shopTaskTitle = sprintf(
                            '%d incoming, %d waiting warehouse',
                            (int) ($shopTasks['incoming'] ?? 0),
                            (int) ($shopTasks['waiting'] ?? 0),
                        );

                        $warehouseTaskBadgeClass = ((int) ($warehouseTasks['received_unacknowledged'] ?? 0)) > 0
                            ? 'bg-red-600 text-white'
                            : 'bg-amber-500 text-white';

                        $shopTaskBadgeClass = ((int) ($shopTasks['incoming'] ?? 0)) > 0
                            ? 'bg-blue-600 text-white'
                            : 'bg-amber-500 text-white';
                    @endphp

                    <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center gap-2 py-3">
                        <a href="{{ route('tenant.dashboard', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Dashboard
                        </a>

                        <a href="{{ route('tenant.invoices.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.invoices.*') || request()->routeIs('tenant.pos.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Invoices
                        </a>

                        <div class="relative group">
                            <button type="button" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.products.*') || request()->routeIs('tenant.mobile-scanner.*') || request()->routeIs('tenant.categories.*') || request()->routeIs('tenant.brands.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span>Catalog</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div class="invisible pointer-events-none absolute left-0 z-30   w-52 rounded-lg border border-slate-200 bg-white shadow-lg opacity-0 transition group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                                <a href="{{ route('tenant.products.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Products</a>
                                <a href="{{ route('tenant.products.barcode-labels.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Barcode Labels</a>
                                <a href="{{ route('tenant.mobile-scanner.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Mobile Scanner</a>
                                <a href="{{ route('tenant.categories.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Categories</a>
                                <a href="{{ route('tenant.brands.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Brands</a>
                            </div>
                        </div>

                        <div class="relative group">
                            <button type="button" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.stock.*') || request()->routeIs('tenant.stock-in.*') || request()->routeIs('tenant.stock-adjustments.*') || request()->routeIs('tenant.stock-movements.*') || request()->routeIs('tenant.stock-transfers.index') || request()->routeIs('tenant.stock-transfers.create') || request()->routeIs('tenant.stock-transfers.show') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span>Inventory</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div class="invisible pointer-events-none absolute left-0 z-30   w-52 rounded-lg border border-slate-200 bg-white shadow-lg opacity-0 transition group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                                <a href="{{ route('tenant.stock.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Stock Levels</a>
                                <a href="{{ route('tenant.stock-in.create', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Stock In</a>
                                <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Transfers</a>
                                <a href="{{ route('tenant.stock-movements.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Movements</a>
                            </div>
                        </div>

                        <a href="{{ route('tenant.after-sales.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.after-sales.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Returns & Warranty
                        </a>

                        <a href="{{ route('tenant.accounting.daily-summary', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Accounting
                        </a>

                        <div class="relative group">
                            <button type="button" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.suppliers.*') || request()->routeIs('tenant.purchases.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span>Purchases</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div class="invisible pointer-events-none absolute left-0 z-30   w-52 rounded-lg border border-slate-200 bg-white shadow-lg opacity-0 transition group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                                <a href="{{ route('tenant.purchases.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Purchase History</a>
                                <a href="{{ route('tenant.suppliers.index', $tenant) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Suppliers</a>
                            </div>
                        </div>

                        <a href="{{ route('tenant.customers.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.customers.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Customers
                        </a>

                        <div class="relative group">
                            <button type="button" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.stock-transfers.shop-tasks') || request()->routeIs('tenant.stock-transfers.warehouse-tasks') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span>Tasks</span>
                                @if(($warehouseTaskTotal + $shopTaskTotal) > 0)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-bold text-white">
                                        {{ (($warehouseTaskTotal + $shopTaskTotal) > 99) ? '99+' : ($warehouseTaskTotal + $shopTaskTotal) }}
                                    </span>
                                @endif
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div class="invisible pointer-events-none absolute right-0 z-30   w-56 rounded-lg border border-slate-200 bg-white shadow-lg opacity-0 transition group-hover:visible group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:visible group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                                <a href="{{ route('tenant.stock-transfers.shop-tasks', $tenant) }}" title="{{ $shopTaskTitle }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span>Shop Task</span>
                                    @if($shopTaskTotal > 0)
                                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-bold {{ $shopTaskBadgeClass }}">
                                            {{ $shopTaskBadge }}
                                        </span>
                                    @endif
                                </a>

                                <a href="{{ route('tenant.stock-transfers.warehouse-tasks', $tenant) }}" title="{{ $warehouseTaskTitle }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span>Warehouse Task</span>
                                    @if($warehouseTaskTotal > 0)
                                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-bold {{ $warehouseTaskBadgeClass }}">
                                            {{ $warehouseTaskBadge }}
                                        </span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
            @endif
        </header>

        <main class="max-w-7xl mx-auto px-4 py-8">
            @if(session('success'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-red-700">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdowns = Array.from(document.querySelectorAll('[data-menu-dropdown]'));

            function closeDropdowns(except = null) {
                dropdowns.forEach(dropdown => {
                    if (dropdown !== except) {
                        dropdown.removeAttribute('open');
                    }
                });
            }

            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('toggle', function () {
                    if (this.open) {
                        closeDropdowns(this);
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('[data-menu-dropdown]')) {
                    closeDropdowns();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeDropdowns();
                }
            });
        });
    </script>
</body>
</html>
