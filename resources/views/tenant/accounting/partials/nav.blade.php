<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 mb-6 overflow-x-auto">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('tenant.accounting.daily-summary', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.daily-summary') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            Summary
        </a>

        <a href="{{ route('tenant.accounting.ledger', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.ledger') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            General Ledger
        </a>

        <a href="{{ route('tenant.accounting.cashbook', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.cashbook') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            Cashbook
        </a>

        <a href="{{ route('tenant.accounting.sales-ledger', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.sales-ledger') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            Sales Ledger
        </a>

        <a href="{{ route('tenant.accounting.customer-ledger', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.customer-ledger') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            Customer Ledger
        </a>

        <a href="{{ route('tenant.accounting.due-collections', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.due-collections') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            Invoice Dues
        </a>

        <a href="{{ route('tenant.accounting.expenses', $tenant) }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tenant.accounting.expenses') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
            Expenses
        </a>
    </div>
</div>
