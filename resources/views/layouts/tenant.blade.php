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
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">{{ $tenant->name ?? 'Gadget ERP' }}</h1>
                    <p class="text-sm text-slate-500">NexproBD Retail SaaS</p>
                </div>

                @if(session()->has('tenant_user_id') && isset($tenant))
                    <div class="flex items-center gap-4">
                        <div class="hidden md:block text-right">
                            <div class="text-sm font-semibold">{{ session('tenant_user_name') }}</div>
                            <div class="text-xs text-slate-500">{{ session('tenant_user_email') }}</div>
                        </div>

                        <form method="POST" action="{{ route('tenant.logout', $tenant) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                                Logout
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @if(session()->has('tenant_user_id') && isset($tenant))
                <nav class="border-t border-slate-200">
                    <div class="max-w-7xl mx-auto px-4 flex flex-wrap gap-2 py-3">
                        <a href="{{ route('tenant.dashboard', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Dashboard
                        </a>

                        <a href="{{ route('tenant.pos.create', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.pos.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            POS
                        </a>

                        <a href="{{ route('tenant.invoices.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.invoices.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Invoices
                        </a>

                        <a href="{{ route('tenant.products.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.products.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Products
                        </a>

                        <a href="{{ route('tenant.stock.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.stock.*') || request()->routeIs('tenant.stock-in.*') || request()->routeIs('tenant.stock-adjustments.*') || request()->routeIs('tenant.stock-movements.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Stock
                        </a>

                        <a href="{{ route('tenant.customers.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.customers.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Customers
                        </a>

                        <a href="{{ route('tenant.categories.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.categories.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Categories
                        </a>

                        <a href="{{ route('tenant.brands.index', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.brands.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Brands
                        </a>

                        <a href="{{ route('tenant.accounting.daily-summary', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Accounting
                        </a>
                        <a href="{{ route('tenant.settings.shop.edit', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.settings.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Settings
                        </a>
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
</body>
</html>
