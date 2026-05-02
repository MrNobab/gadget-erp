<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Services\LedgerService;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Illuminate\Console\Command;

class BackfillLedgerFromInvoices extends Command
{
    protected $signature = 'nxpbd:backfill-ledger';

    protected $description = 'Backfill ledger entries for existing invoices and payments';

    public function handle(LedgerService $ledgerService): int
    {
        $invoiceCount = 0;
        $paymentCount = 0;

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($ledgerService, &$invoiceCount, &$paymentCount): void {
            TenantContext::set($tenant);

            Invoice::query()
                ->with(['items', 'customer'])
                ->where('status', Invoice::STATUS_POSTED)
                ->orderBy('id')
                ->each(function (Invoice $invoice) use ($ledgerService, &$invoiceCount): void {
                    $ledgerService->recordInvoicePosted($invoice, (int) ($invoice->created_by ?? 0));
                    $invoiceCount++;
                });

            Payment::query()
                ->with(['invoice', 'customer'])
                ->orderBy('id')
                ->each(function (Payment $payment) use ($ledgerService, &$paymentCount): void {
                    $ledgerService->recordPaymentReceived($payment, (int) ($payment->created_by ?? 0));
                    $paymentCount++;
                });

            TenantContext::clear();
        });

        $this->info("Invoices processed: {$invoiceCount}");
        $this->info("Payments processed: {$paymentCount}");

        return self::SUCCESS;
    }
}
