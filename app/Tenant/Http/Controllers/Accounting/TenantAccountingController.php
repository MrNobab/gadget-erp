<?php

namespace App\Tenant\Http\Controllers\Accounting;

use App\Domain\Accounting\Models\Expense;
use App\Domain\Accounting\Models\LedgerEntry;
use App\Domain\Accounting\Services\LedgerService;
use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Services\SalesService;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TenantAccountingController extends Controller
{
    public function ledger(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request);
        $accountType = $request->query('account_type');

        $entries = $this->ledgerQuery($filters, $accountType)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $totals = $this->ledgerTotals($filters, $accountType);

        return view('tenant.accounting.ledger', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'entries' => $entries,
            'accountType' => $accountType,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'accountTypes' => $this->accountTypes(),
            'totals' => $totals,
        ]);
    }

    public function downloadLedger(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request);
        $accountType = $request->query('account_type');

        $entries = $this->ledgerQuery($filters, $accountType)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $rows = $entries->map(fn (LedgerEntry $entry): array => [
            $entry->entry_date->format('d M Y'),
            $this->accountLabel($entry->account_type),
            $this->friendlyLedgerAction($entry),
            $entry->customer?->name ?? '-',
            $this->money($tenant, $this->friendlyLedgerAmount($entry)),
            $entry->notes ?? '-',
        ])->toArray();

        return $this->exportReport($request, $tenant, [
            'title' => 'Ledger Report',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => ['Date', 'Category', 'Action', 'Customer', 'Amount', 'Details'],
            'rows' => $rows,
            'totals' => [
                'Total Sales' => $this->money($tenant, $entries->where('account_type', LedgerEntry::ACCOUNT_SALES)->sum('credit')),
                'Total Due Added' => $this->money($tenant, $entries->where('account_type', LedgerEntry::ACCOUNT_DUE)->sum('debit')),
                'Total Due Collected' => $this->money($tenant, $entries->where('account_type', LedgerEntry::ACCOUNT_DUE)->sum('credit')),
            ],
        ]);
    }

    public function cashbook(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request);
        $accountType = $request->query('account_type');

        $entries = $this->cashbookQuery($filters, $accountType)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $totals = $this->cashbookTotals($filters, $accountType);

        return view('tenant.accounting.cashbook', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'entries' => $entries,
            'accountType' => $accountType,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'totalIn' => $totals['totalIn'],
            'totalOut' => $totals['totalOut'],
            'balance' => $totals['balance'],
            'paymentAccounts' => $this->paymentAccounts(),
        ]);
    }

    public function downloadCashbook(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request);
        $accountType = $request->query('account_type');

        $entries = $this->cashbookQuery($filters, $accountType)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $totals = $this->cashbookTotals($filters, $accountType);

        $rows = $entries->map(fn (LedgerEntry $entry): array => [
            $entry->entry_date->format('d M Y'),
            $this->accountLabel($entry->account_type),
            $this->money($tenant, $entry->debit),
            $this->money($tenant, $entry->credit),
            $entry->notes ?? '-',
        ])->toArray();

        return $this->exportReport($request, $tenant, [
            'title' => 'Cashbook',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => ['Date', 'Account', 'Money In', 'Money Out', 'Details'],
            'rows' => $rows,
            'totals' => [
                'Total In' => $this->money($tenant, $totals['totalIn']),
                'Total Out' => $this->money($tenant, $totals['totalOut']),
                'Balance' => $this->money($tenant, $totals['balance']),
            ],
        ]);
    }

    public function salesLedger(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request);

        $entries = $this->salesLedgerQuery($filters)
            ->paginate(30)
            ->withQueryString();

        $totalSales = LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_SALES)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('credit');

        return view('tenant.accounting.sales-ledger', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'entries' => $entries,
            'totalSales' => $totalSales,
            'from' => $filters['from'],
            'to' => $filters['to'],
        ]);
    }

    public function downloadSalesLedger(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request);
        $entries = $this->salesLedgerQuery($filters)->get();

        $rows = $entries->map(fn ($entry): array => [
            Carbon::parse($entry->entry_date)->format('d M Y'),
            $this->money($tenant, $entry->total_sales),
        ])->toArray();

        return $this->exportReport($request, $tenant, [
            'title' => 'Sales Ledger',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => ['Date', 'Sales'],
            'rows' => $rows,
            'totals' => [
                'Total Sales' => $this->money($tenant, $entries->sum('total_sales')),
            ],
        ]);
    }

    public function customerLedger(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request);
        $customerSearch = trim((string) $request->query('customer_search'));

        $baseQuery = $this->customerDueSummaryQuery($filters, $customerSearch);

        $customerRows = (clone $baseQuery)
            ->orderByDesc('total_due')
            ->paginate(30)
            ->withQueryString();

        $summary = $this->customerDueSummaryTotals($filters, $customerSearch);

        return view('tenant.accounting.customer-ledger', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'customerRows' => $customerRows,
            'customerSearch' => $customerSearch,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'totalPurchase' => $summary['totalPurchase'],
            'totalPaid' => $summary['totalPaid'],
            'totalDue' => $summary['totalDue'],
        ]);
    }

    public function downloadCustomerLedger(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request);
        $customerSearch = trim((string) $request->query('customer_search'));

        $rowsData = $this->customerDueSummaryQuery($filters, $customerSearch)
            ->orderByDesc('total_due')
            ->get();

        $rows = $rowsData->map(fn ($row): array => [
            $row->customer?->name ?? '-',
            $row->customer?->phone ?? '-',
            $this->money($tenant, $row->total_purchase),
            $this->money($tenant, $row->total_paid),
            $this->money($tenant, $row->total_due),
        ])->toArray();

        $summary = $this->customerDueSummaryTotals($filters, $customerSearch);

        return $this->exportReport($request, $tenant, [
            'title' => 'Customer Due Ledger',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => ['Customer', 'Phone', 'Total Purchase', 'Total Paid', 'Total Due'],
            'rows' => $rows,
            'totals' => [
                'Total Purchase' => $this->money($tenant, $summary['totalPurchase']),
                'Total Paid' => $this->money($tenant, $summary['totalPaid']),
                'Total Due' => $this->money($tenant, $summary['totalDue']),
            ],
        ]);
    }

    public function collectCustomerDue(Request $request, Tenant $tenant, int $customerId, SalesService $salesService): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customerId);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,mobile_money,card'],
            'reference' => ['nullable', 'string', 'max:150'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['notes'] = $validated['notes'] ?? 'Due collected from customer ledger page';

        try {
            $salesService->recordCustomerDuePayment(
                $customer,
                $validated,
                (int) $request->session()->get('tenant_user_id')
            );
        } catch (Throwable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Customer due payment collected successfully.');
    }

    public function dueCollections(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request);
        $customerSearch = trim((string) $request->query('customer_search'));
        $paymentStatus = $request->query('payment_status');

        $invoices = $this->dueInvoicesQuery($filters, $customerSearch, $paymentStatus)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $summaryQuery = $this->dueInvoicesQuery($filters, $customerSearch, $paymentStatus);

        return view('tenant.accounting.due-collections', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'invoices' => $invoices,
            'customerSearch' => $customerSearch,
            'paymentStatus' => $paymentStatus,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'totalInvoiceAmount' => (clone $summaryQuery)->sum('total'),
            'totalPaidAmount' => (clone $summaryQuery)->sum('paid_amount'),
            'totalDueAmount' => (clone $summaryQuery)->sum('due_amount'),
        ]);
    }

    public function downloadDueCollections(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request);
        $customerSearch = trim((string) $request->query('customer_search'));
        $paymentStatus = $request->query('payment_status');

        $invoices = $this->dueInvoicesQuery($filters, $customerSearch, $paymentStatus)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $rows = $invoices->map(fn (Invoice $invoice): array => [
            $invoice->invoice_date->format('d M Y'),
            $invoice->invoice_number,
            $invoice->customer?->name ?? '-',
            $invoice->customer?->phone ?? '-',
            $this->money($tenant, $invoice->previous_due),
            $this->money($tenant, $invoice->total),
            $this->money($tenant, $invoice->paid_amount),
            $this->money($tenant, $invoice->due_amount),
            $this->money($tenant, $invoice->customer?->total_due ?? 0),
            ucfirst($invoice->payment_status),
            $invoice->creator?->name ?? '-',
        ])->toArray();

        return $this->exportReport($request, $tenant, [
            'title' => 'Due Invoice Report',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => [
                'Date',
                'Invoice',
                'Customer',
                'Phone',
                'Previous Due',
                'Invoice Total',
                'Paid',
                'Invoice Due',
                'Customer Total Due',
                'Status',
                'Created By',
            ],
            'rows' => $rows,
            'totals' => [
                'Total Invoice Amount' => $this->money($tenant, $invoices->sum('total')),
                'Total Paid Amount' => $this->money($tenant, $invoices->sum('paid_amount')),
                'Total Invoice Due' => $this->money($tenant, $invoices->sum('due_amount')),
            ],
        ]);
    }

    public function collectInvoiceDue(Request $request, Tenant $tenant, int $invoiceId, SalesService $salesService): RedirectResponse
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,mobile_money,card'],
            'reference' => ['nullable', 'string', 'max:150'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['notes'] = $validated['notes'] ?? 'Due collected from invoice dues page';

        try {
            $salesService->recordPayment(
                $invoice,
                $validated,
                (int) $request->session()->get('tenant_user_id')
            );
        } catch (Throwable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Invoice due payment collected successfully.');
    }

    public function expenses(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request);

        $expenses = $this->expensesQuery($filters)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalExpense = $this->expensesQuery($filters)->sum('amount');

        return view('tenant.accounting.expenses', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'expenses' => $expenses,
            'totalExpense' => $totalExpense,
            'from' => $filters['from'],
            'to' => $filters['to'],
        ]);
    }

    public function downloadExpenses(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request);

        $expenses = $this->expensesQuery($filters)
            ->with('creator')
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $rows = $expenses->map(fn (Expense $expense): array => [
            $expense->expense_date->format('d M Y'),
            $expense->category,
            $this->accountLabel($expense->payment_method),
            $expense->reference ?? '-',
            $this->money($tenant, $expense->amount),
            $expense->description ?? '-',
            $expense->creator?->name ?? '-',
        ])->toArray();

        return $this->exportReport($request, $tenant, [
            'title' => 'Expense Report',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => ['Date', 'Category', 'Method', 'Reference', 'Amount', 'Description', 'Entry By'],
            'rows' => $rows,
            'totals' => [
                'Total Expense' => $this->money($tenant, $expenses->sum('amount')),
            ],
        ]);
    }

    public function storeExpense(Request $request, Tenant $tenant, LedgerService $ledgerService): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank,mobile_money,card'],
            'reference' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $expense = Expense::query()->create([
                'expense_date' => $validated['expense_date'],
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_by' => $request->session()->get('tenant_user_id'),
            ]);

            $ledgerService->recordExpense($expense, (int) $request->session()->get('tenant_user_id'));
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['expense' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('tenant.accounting.expenses', $tenant)
            ->with('success', 'Expense saved and ledger updated successfully.');
    }

    public function dailySummary(Request $request, Tenant $tenant): View
    {
        $filters = $this->dateFilters($request, true);
        $summary = $this->summaryTotals($filters);

        return view('tenant.accounting.daily-summary', array_merge([
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'from' => $filters['from'],
            'to' => $filters['to'],
        ], $summary));
    }

    public function downloadDailySummary(Request $request, Tenant $tenant)
    {
        $filters = $this->dateFilters($request, true);
        $summary = $this->summaryTotals($filters);

        $rows = [
            ['Total Sales', $this->money($tenant, $summary['totalSales'])],
            ['Cost of Goods', $this->money($tenant, $summary['costOfGoods'])],
            ['Gross Profit', $this->money($tenant, $summary['grossProfit'])],
            ['Due Increased', $this->money($tenant, $summary['dueIncreased'])],
            ['Due Collected', $this->money($tenant, $summary['dueCollected'])],
            ['Net Due Change', $this->money($tenant, $summary['netDueChange'])],
            ['Collections', $this->money($tenant, $summary['collections'])],
            ['Expenses', $this->money($tenant, $summary['expenses'])],
            ['Net Profit', $this->money($tenant, $summary['netProfit'])],
        ];

        return $this->exportReport($request, $tenant, [
            'title' => 'Summary Report',
            'subtitle' => $this->rangeSubtitle($filters),
            'headers' => ['Metric', 'Amount'],
            'rows' => $rows,
            'totals' => [],
        ]);
    }

    private function ledgerQuery(array $filters, ?string $accountType)
    {
        return LedgerEntry::query()
            ->with(['customer', 'creator'])
            ->when(! $accountType, fn ($query) => $query->where('account_type', '!=', LedgerEntry::ACCOUNT_INVENTORY_ASSET))
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->when($accountType, fn ($query) => $query->where('account_type', $accountType));
    }

    private function ledgerTotals(array $filters, ?string $accountType): array
    {
        $query = $this->ledgerQuery($filters, $accountType);

        return [
            'sales' => (clone $query)->where('account_type', LedgerEntry::ACCOUNT_SALES)->sum('credit'),
            'dueAdded' => (clone $query)->where('account_type', LedgerEntry::ACCOUNT_DUE)->sum('debit'),
            'dueCollected' => (clone $query)->where('account_type', LedgerEntry::ACCOUNT_DUE)->sum('credit'),
            'moneyIn' => (clone $query)->whereIn('account_type', $this->paymentAccounts())->sum('debit'),
            'moneyOut' => (clone $query)->whereIn('account_type', $this->paymentAccounts())->sum('credit'),
            'expenses' => (clone $query)->where('account_type', LedgerEntry::ACCOUNT_EXPENSE)->sum('debit'),
            'costOfGoods' => (clone $query)->where('account_type', LedgerEntry::ACCOUNT_COST_OF_GOODS)->sum('debit'),
        ];
    }

    private function cashbookQuery(array $filters, ?string $accountType)
    {
        return LedgerEntry::query()
            ->with(['customer', 'creator'])
            ->whereIn('account_type', $this->paymentAccounts())
            ->when($accountType, fn ($query) => $query->where('account_type', $accountType))
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']));
    }

    private function cashbookTotals(array $filters, ?string $accountType): array
    {
        $query = $this->cashbookQuery($filters, $accountType);

        $totalIn = (clone $query)->sum('debit');
        $totalOut = (clone $query)->sum('credit');

        return [
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'balance' => (float) $totalIn - (float) $totalOut,
        ];
    }

    private function salesLedgerQuery(array $filters)
    {
        return LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_SALES)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->selectRaw('entry_date, SUM(credit) as total_sales')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date');
    }

    private function customerDueBaseQuery(array $filters, string $customerSearch)
    {
        return LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_DUE)
            ->whereNotNull('customer_id')
            ->when($customerSearch !== '', function ($query) use ($customerSearch): void {
                $query->whereHas('customer', function ($customerQuery) use ($customerSearch): void {
                    $customerQuery->where('name', 'like', '%' . $customerSearch . '%')
                        ->orWhere('phone', 'like', '%' . $customerSearch . '%')
                        ->orWhere('email', 'like', '%' . $customerSearch . '%');
                });
            })
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']));
    }

    private function customerDueSummaryQuery(array $filters, string $customerSearch)
    {
        return $this->customerDueBaseQuery($filters, $customerSearch)
            ->with('customer')
            ->selectRaw('customer_id, SUM(debit) as total_purchase, SUM(credit) as total_paid, SUM(debit - credit) as total_due')
            ->groupBy('customer_id')
            ->havingRaw('SUM(debit) > 0 OR SUM(credit) > 0');
    }

    private function customerDueSummaryTotals(array $filters, string $customerSearch): array
    {
        $base = $this->customerDueBaseQuery($filters, $customerSearch);

        $totalPurchase = (clone $base)->sum('debit');
        $totalPaid = (clone $base)->sum('credit');

        return [
            'totalPurchase' => $totalPurchase,
            'totalPaid' => $totalPaid,
            'totalDue' => (float) $totalPurchase - (float) $totalPaid,
        ];
    }

    private function dueInvoicesQuery(array $filters, string $customerSearch, mixed $paymentStatus)
    {
        return Invoice::query()
            ->with(['customer', 'creator'])
            ->where('status', Invoice::STATUS_POSTED)
            ->where('due_amount', '>', 0)
            ->when($customerSearch !== '', function ($query) use ($customerSearch): void {
                $query->whereHas('customer', function ($customerQuery) use ($customerSearch): void {
                    $customerQuery->where('name', 'like', '%' . $customerSearch . '%')
                        ->orWhere('phone', 'like', '%' . $customerSearch . '%')
                        ->orWhere('email', 'like', '%' . $customerSearch . '%');
                });
            })
            ->when($filters['from'], fn ($query) => $query->whereDate('invoice_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('invoice_date', '<=', $filters['to']))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus));
    }

    private function expensesQuery(array $filters)
    {
        return Expense::query()
            ->with(['creator'])
            ->when($filters['from'], fn ($query) => $query->whereDate('expense_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('expense_date', '<=', $filters['to']));
    }

    private function summaryTotals(array $filters): array
    {
        $totalSales = LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_SALES)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('credit');

        $costOfGoods = LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_COST_OF_GOODS)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('debit');

        $collections = LedgerEntry::query()
            ->whereIn('account_type', $this->paymentAccounts())
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('debit');

        $expenses = LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_EXPENSE)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('debit');

        $dueIncreased = LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_DUE)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('debit');

        $dueCollected = LedgerEntry::query()
            ->where('account_type', LedgerEntry::ACCOUNT_DUE)
            ->when($filters['from'], fn ($query) => $query->whereDate('entry_date', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('entry_date', '<=', $filters['to']))
            ->sum('credit');

        $grossProfit = (float) $totalSales - (float) $costOfGoods;

        return [
            'totalSales' => $totalSales,
            'costOfGoods' => $costOfGoods,
            'grossProfit' => $grossProfit,
            'collections' => $collections,
            'expenses' => $expenses,
            'netProfit' => $grossProfit - (float) $expenses,
            'dueIncreased' => $dueIncreased,
            'dueCollected' => $dueCollected,
            'netDueChange' => (float) $dueIncreased - (float) $dueCollected,
        ];
    }

    private function friendlyLedgerAction(LedgerEntry $entry): string
    {
        if ($entry->account_type === LedgerEntry::ACCOUNT_SALES) {
            return 'Sale Recorded';
        }

        if ($entry->account_type === LedgerEntry::ACCOUNT_DUE && (float) $entry->debit > 0) {
            return 'Due Added';
        }

        if ($entry->account_type === LedgerEntry::ACCOUNT_DUE && (float) $entry->credit > 0) {
            return 'Due Collected';
        }

        if (in_array($entry->account_type, $this->paymentAccounts(), true) && (float) $entry->debit > 0) {
            return 'Money Received';
        }

        if (in_array($entry->account_type, $this->paymentAccounts(), true) && (float) $entry->credit > 0) {
            return 'Money Paid Out';
        }

        if ($entry->account_type === LedgerEntry::ACCOUNT_EXPENSE) {
            return 'Expense';
        }

        if ($entry->account_type === LedgerEntry::ACCOUNT_COST_OF_GOODS) {
            return 'Product Cost';
        }

        return 'Entry';
    }

    private function friendlyLedgerAmount(LedgerEntry $entry): float
    {
        return (float) $entry->debit > 0 ? (float) $entry->debit : (float) $entry->credit;
    }

    private function dateFilters(Request $request, bool $defaultToday = false): array
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if ($defaultToday && ! $from && ! $to) {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    private function paymentAccounts(): array
    {
        return [
            LedgerEntry::ACCOUNT_CASH,
            LedgerEntry::ACCOUNT_BANK,
            LedgerEntry::ACCOUNT_MOBILE_MONEY,
            LedgerEntry::ACCOUNT_CARD,
        ];
    }

    private function accountTypes(): array
    {
        return [
            LedgerEntry::ACCOUNT_SALES => 'Sales',
            LedgerEntry::ACCOUNT_DUE => 'Due',
            LedgerEntry::ACCOUNT_CASH => 'Cash',
            LedgerEntry::ACCOUNT_BANK => 'Bank',
            LedgerEntry::ACCOUNT_MOBILE_MONEY => 'Mobile Money',
            LedgerEntry::ACCOUNT_CARD => 'Card',
            LedgerEntry::ACCOUNT_EXPENSE => 'Expense',
            LedgerEntry::ACCOUNT_COST_OF_GOODS => 'Product Cost',
            LedgerEntry::ACCOUNT_INVENTORY_ASSET => 'Inventory Asset',
        ];
    }

    private function accountLabel(?string $account): string
    {
        if ($account === 'receivable' || $account === 'due') {
            return 'Due';
        }

        return $this->accountTypes()[$account] ?? ucwords(str_replace('_', ' ', (string) $account));
    }

    private function settingsWithDefaults(Tenant $tenant): array
    {
        return array_merge([
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'currency_position' => 'before',
            'logo_path' => null,
            'shop_phone' => $tenant->owner_phone,
            'shop_email' => $tenant->owner_email,
            'shop_address' => '',
        ], $tenant->settings ?? []);
    }

    private function money(Tenant $tenant, mixed $amount): string
    {
        $settings = $this->settingsWithDefaults($tenant);
        $symbol = $settings['currency_symbol'] ?? '৳';
        $position = $settings['currency_position'] ?? 'before';
        $formatted = number_format((float) $amount, 2);

        return $position === 'after' ? $formatted . $symbol : $symbol . $formatted;
    }

    private function rangeSubtitle(array $filters): string
    {
        if ($filters['from'] && $filters['to']) {
            return 'From ' . Carbon::parse($filters['from'])->format('d M Y') . ' to ' . Carbon::parse($filters['to'])->format('d M Y');
        }

        if ($filters['from']) {
            return 'From ' . Carbon::parse($filters['from'])->format('d M Y');
        }

        if ($filters['to']) {
            return 'Until ' . Carbon::parse($filters['to'])->format('d M Y');
        }

        return 'All dates';
    }

    private function exportReport(Request $request, Tenant $tenant, array $report)
    {
        $format = $request->query('format', 'pdf');
        $filename = Str::slug($tenant->slug . '-' . $report['title'] . '-' . now()->format('Ymd-His'));

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($report): void {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, [$report['title']]);
                fputcsv($handle, [$report['subtitle'] ?? '']);
                fputcsv($handle, []);
                fputcsv($handle, $report['headers']);

                foreach ($report['rows'] as $row) {
                    fputcsv($handle, $row);
                }

                if (! empty($report['totals'])) {
                    fputcsv($handle, []);

                    foreach ($report['totals'] as $label => $value) {
                        fputcsv($handle, [$label, $value]);
                    }
                }

                fclose($handle);
            }, $filename . '.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        $pdf = Pdf::loadView('tenant.accounting.report-pdf', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'logoDataUri' => $this->logoDataUri($tenant),
            'report' => $report,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($filename . '.pdf');
    }

    private function logoDataUri(Tenant $tenant): ?string
    {
        $settings = $tenant->settings ?? [];
        $logoPath = $settings['logo_path'] ?? null;

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($logoPath);
        $mime = mime_content_type($fullPath) ?: 'image/png';
        $data = base64_encode((string) file_get_contents($fullPath));

        return "data:{$mime};base64,{$data}";
    }
}
