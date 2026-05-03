<?php

namespace App\Tenant\Http\Controllers\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseStock;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Services\SalesService;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use App\Support\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TenantSalesController extends Controller
{
    public function pos(Tenant $tenant): View
    {
        $settings = $this->settingsWithDefaults($tenant);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stocks = WarehouseStock::query()
            ->with(['product'])
            ->get();

        $stockMatrix = [];

        foreach ($stocks as $stock) {
            $stockMatrix[$stock->warehouse_id][$stock->product_id] = [
                'quantity' => (int) $stock->quantity,
                'average_cost_price' => (float) $stock->average_cost_price,
            ];
        }

        return view('tenant.sales.pos.create', [
            'tenant' => $tenant,
            'settings' => $settings,
            'warehouses' => Warehouse::query()->where('is_active', true)->where('type', 'shop')->orderByDesc('is_default')->orderBy('name')->get(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'productPayload' => $products->mapWithKeys(function (Product $product): array {
                return [
                    $product->id => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                        'scan_code' => $product->barcodeValue(),
                        'sale_price' => (float) $product->sale_price,
                    ],
                ];
            })->toArray(),
            'stockMatrix' => $stockMatrix,
        ]);
    }

    public function storeInvoice(Request $request, Tenant $tenant, SalesService $salesService): RedirectResponse
    {
        $settings = $this->settingsWithDefaults($tenant);

        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'quick_customer_name' => ['nullable', 'string', 'max:150'],
            'quick_customer_phone' => ['nullable', 'string', 'max:30'],
            'quick_customer_email' => ['nullable', 'email', 'max:150'],
            'quick_customer_address' => ['nullable', 'string', 'max:1000'],
            'invoice_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,bank,mobile_money,card'],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validated['customer_mode'] === 'new') {
            if (empty($validated['quick_customer_name']) || empty($validated['quick_customer_phone'])) {
                return back()
                    ->withErrors(['customer' => 'Customer name and phone are required when creating a new customer.'])
                    ->withInput();
            }

            $customer = Customer::query()->firstOrCreate(
                ['phone' => $validated['quick_customer_phone']],
                [
                    'name' => $validated['quick_customer_name'],
                    'email' => $validated['quick_customer_email'] ?? null,
                    'address' => $validated['quick_customer_address'] ?? null,
                    'city' => null,
                    'is_active' => true,
                ]
            );

            $validated['customer_id'] = $customer->id;
        }

        if (empty($validated['customer_id'])) {
            return back()
                ->withErrors(['customer_id' => 'Please select an existing customer or create a new customer.'])
                ->withInput();
        }

        $validated['tax_percent'] = (float) ($settings['tax_percent'] ?? 0);

        try {
            $invoice = $salesService->createPostedInvoice(
                $validated,
                (int) $request->session()->get('tenant_user_id')
            );
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['invoice' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('tenant.invoices.show', [$tenant, $invoice->id])
            ->with('success', 'Invoice posted successfully.');
    }

    public function invoices(Request $request, Tenant $tenant): View
    {
        $search = trim((string) $request->query('search'));
        $paymentStatus = $request->query('payment_status');

        $invoices = Invoice::query()
            ->with(['customer'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('invoice_number', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tenant.sales.invoices.index', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'invoices' => $invoices,
            'search' => $search,
            'paymentStatus' => $paymentStatus,
        ]);
    }

    public function showInvoice(Tenant $tenant, int $invoiceId): View
    {
        $invoice = Invoice::query()
            ->with(['customer', 'warehouse', 'items.product', 'payments.creator', 'creator'])
            ->findOrFail($invoiceId);

        return view('tenant.sales.invoices.show', [
            'tenant' => $tenant,
            'settings' => $this->settingsWithDefaults($tenant),
            'invoice' => $invoice,
        ]);
    }

    public function storePayment(Request $request, Tenant $tenant, int $invoiceId, SalesService $salesService): RedirectResponse
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,mobile_money,card'],
            'reference' => ['nullable', 'string', 'max:150'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $salesService->recordPayment(
                $invoice,
                $validated,
                (int) $request->session()->get('tenant_user_id')
            );
        } catch (Throwable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('tenant.invoices.show', [$tenant, $invoice->id])
            ->with('success', 'Payment recorded successfully.');
    }

    public function downloadInvoicePdf(Tenant $tenant, int $invoiceId)
    {
        $invoice = Invoice::query()
            ->with(['customer', 'warehouse', 'items.product', 'payments.creator', 'creator'])
            ->findOrFail($invoiceId);

        $settings = $this->settingsWithDefaults($tenant);
        $logoDataUri = $this->logoDataUri($settings);

        $pdf = Pdf::loadView('tenant.sales.invoices.pdf', [
            'tenant' => $tenant,
            'settings' => $settings,
            'invoice' => $invoice,
            'logoDataUri' => $logoDataUri,
            'amountInWords' => $this->amountInWords((float) $invoice->total),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($invoice->invoice_number . '.pdf');
    }

    private function logoDataUri(array $settings): ?string
    {
        $logoPath = $settings['logo_path'] ?? null;

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($logoPath);
        $mime = mime_content_type($fullPath) ?: 'image/png';
        $data = base64_encode((string) file_get_contents($fullPath));

        return "data:{$mime};base64,{$data}";
    }

    private function amountInWords(float $amount): string
    {
        $number = (int) round($amount);

        if ($number === 0) {
            return 'ZERO TAKA ONLY';
        }

        $words = $this->numberToWords($number);

        return strtoupper($words . ' TAKA ONLY');
    }

    private function numberToWords(int $number): string
    {
        $ones = [
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
        ];

        $tens = [
            2 => 'twenty',
            3 => 'thirty',
            4 => 'forty',
            5 => 'fifty',
            6 => 'sixty',
            7 => 'seventy',
            8 => 'eighty',
            9 => 'ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            return trim($tens[intdiv($number, 10)] . ' ' . $ones[$number % 10]);
        }

        if ($number < 1000) {
            return trim($ones[intdiv($number, 100)] . ' hundred ' . $this->numberToWords($number % 100));
        }

        if ($number < 100000) {
            return trim($this->numberToWords(intdiv($number, 1000)) . ' thousand ' . $this->numberToWords($number % 1000));
        }

        if ($number < 10000000) {
            return trim($this->numberToWords(intdiv($number, 100000)) . ' lakh ' . $this->numberToWords($number % 100000));
        }

        return trim($this->numberToWords(intdiv($number, 10000000)) . ' crore ' . $this->numberToWords($number % 10000000));
    }

    private function settingsWithDefaults(Tenant $tenant): array
    {
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
        ], $tenant->settings ?? []);
    }
}
