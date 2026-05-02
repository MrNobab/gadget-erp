@extends('layouts.tenant')

@section('content')
    @php
        $symbol = $settings['currency_symbol'] ?? '৳';
        $position = $settings['currency_position'] ?? 'before';
        $money = fn ($amount) => $position === 'after'
            ? number_format((float) $amount, 2) . $symbol
            : $symbol . number_format((float) $amount, 2);

        $downloadQuery = request()->query();
    @endphp

    @include('tenant.accounting.partials.nav')

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Expenses</h2>
            <p class="text-slate-500">Record operating expenses and update ledger.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.expenses.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.expenses.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Add Expense</h3>

            <form method="POST" action="{{ route('tenant.accounting.expenses.store', $tenant) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700">Date</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" placeholder="Rent, Salary, Utilities..." required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Payment Method</label>
                    <select name="payment_method" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="card">Card</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Reference</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                    Save Expense
                </button>
            </form>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm mb-4">
                <div class="text-sm text-slate-500">Total Expense</div>
                <div class="text-3xl font-bold mt-2">{{ $money($totalExpense) }}</div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
                <form method="GET" action="{{ route('tenant.accounting.expenses', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
                    <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">
                    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Filter</button>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Date</th>
                            <th class="text-left px-4 py-3">Category</th>
                            <th class="text-left px-4 py-3">Method</th>
                            <th class="text-right px-4 py-3">Amount</th>
                            <th class="text-left px-4 py-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($expenses as $expense)
                            <tr>
                                <td class="px-4 py-3">{{ $expense->expense_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $expense->category }}</td>
                                <td class="px-4 py-3">{{ ucwords(str_replace('_', ' ', $expense->payment_method)) }}</td>
                                <td class="px-4 py-3 text-right">{{ $money($expense->amount) }}</td>
                                <td class="px-4 py-3">{{ $expense->description ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No expenses found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $expenses->links() }}</div>
        </div>
    </div>
@endsection
