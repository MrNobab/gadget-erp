<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Gadget ERP Admin' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">Gadget ERP</h1>
                    <p class="text-sm text-slate-500">NexproBD Super Admin</p>
                </div>

                @if(session()->has('super_admin_id'))
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                            Logout
                        </button>
                    </form>
                @endif
            </div>

            @if(session()->has('super_admin_id'))
                <nav class="border-t border-slate-200">
                    <div class="max-w-7xl mx-auto px-4 flex flex-wrap gap-2 py-3">
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Dashboard
                        </a>

                        <a href="{{ route('admin.tenants.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.tenants.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Tenants
                        </a>

                        <a href="{{ route('admin.plans.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.plans.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Plans
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
