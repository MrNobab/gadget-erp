<?php

namespace App\Tenant\Http\Controllers\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Scanning\Models\MobileScannerScan;
use App\Domain\Scanning\Models\MobileScannerSession;
use App\Http\Controllers\Controller;
use App\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantMobileScannerController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('tenant.sales.mobile-scanner.index', [
            'tenant' => $tenant,
            'sessions' => MobileScannerSession::query()
                ->with('creator')
                ->where('status', MobileScannerSession::STATUS_ACTIVE)
                ->where('expires_at', '>', now())
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function connect(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'pair_code' => ['required', 'string', 'max:12'],
        ]);

        $session = $this->findUsableSessionByPairCode($validated['pair_code']);

        if (! $session) {
            return back()
                ->withErrors(['pair_code' => 'This pairing code is invalid or expired.'])
                ->withInput();
        }

        return redirect()->route('tenant.mobile-scanner.show', [$tenant, $session->token]);
    }

    public function show(Tenant $tenant, string $token): View
    {
        $session = $this->findUsableSessionByToken($token);

        abort_unless($session, 404);

        $session->update(['last_seen_at' => now()]);

        return view('tenant.sales.mobile-scanner.show', [
            'tenant' => $tenant,
            'scannerSession' => $session,
        ]);
    }

    public function storeSession(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $session = MobileScannerSession::query()->create([
            'token' => Str::random(48),
            'pair_code' => $this->freshPairCode(),
            'name' => $validated['name'] ?? 'POS scanner',
            'status' => MobileScannerSession::STATUS_ACTIVE,
            'created_by' => (int) $request->session()->get('tenant_user_id'),
            'last_seen_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return response()->json($this->sessionPayload($tenant, $session));
    }

    public function poll(Tenant $tenant, string $token): JsonResponse
    {
        $session = $this->findUsableSessionByToken($token);

        if (! $session) {
            return response()->json([
                'active' => false,
                'message' => 'Mobile scanner session has expired or was closed.',
                'scans' => [],
            ], 404);
        }

        $scans = MobileScannerScan::query()
            ->with('product')
            ->where('mobile_scanner_session_id', $session->id)
            ->where('status', MobileScannerScan::STATUS_PENDING)
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($scans->isNotEmpty()) {
            MobileScannerScan::query()
                ->whereIn('id', $scans->pluck('id'))
                ->update([
                    'status' => MobileScannerScan::STATUS_CONSUMED,
                    'consumed_at' => now(),
                ]);
        }

        $session->update(['last_seen_at' => now()]);

        return response()->json([
            'active' => true,
            'scans' => $scans->map(fn (MobileScannerScan $scan): array => [
                'id' => $scan->id,
                'code' => $scan->code,
                'quantity' => $scan->quantity,
                'product_id' => $scan->product_id,
                'product' => $scan->product ? [
                    'id' => $scan->product->id,
                    'name' => $scan->product->name,
                    'sku' => $scan->product->sku,
                    'barcode' => $scan->product->barcode,
                    'sale_price' => (float) $scan->product->sale_price,
                ] : null,
            ])->values(),
        ]);
    }

    public function storeScan(Request $request, Tenant $tenant, string $token): JsonResponse
    {
        $session = $this->findUsableSessionByToken($token);

        if (! $session) {
            return response()->json([
                'ok' => false,
                'message' => 'Scanner pairing expired. Please pair this phone again.',
            ], 404);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:150'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $code = trim($validated['code']);
        $product = $this->findProductByScanCode($code);

        MobileScannerScan::query()->create([
            'mobile_scanner_session_id' => $session->id,
            'product_id' => $product?->id,
            'code' => $code,
            'quantity' => (int) ($validated['quantity'] ?? 1),
            'status' => MobileScannerScan::STATUS_PENDING,
            'scanned_by' => (int) $request->session()->get('tenant_user_id'),
            'scanned_at' => now(),
        ]);

        $session->update(['last_seen_at' => now()]);

        return response()->json([
            'ok' => true,
            'found' => (bool) $product,
            'message' => $product ? 'Scanned ' . $product->name : 'Code sent, but no product matched this barcode.',
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
            ] : null,
        ]);
    }

    public function close(Tenant $tenant, string $token): JsonResponse
    {
        $session = MobileScannerSession::query()
            ->where('token', $token)
            ->first();

        if (! $session) {
            return response()->json(['ok' => true]);
        }

        $session->update([
            'status' => MobileScannerSession::STATUS_CLOSED,
            'last_seen_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function findUsableSessionByToken(string $token): ?MobileScannerSession
    {
        $session = MobileScannerSession::query()
            ->where('token', $token)
            ->first();

        return $session?->isUsable() ? $session : null;
    }

    private function findUsableSessionByPairCode(string $pairCode): ?MobileScannerSession
    {
        $session = MobileScannerSession::query()
            ->where('pair_code', strtoupper(trim($pairCode)))
            ->first();

        return $session?->isUsable() ? $session : null;
    }

    private function findProductByScanCode(string $code): ?Product
    {
        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($code): void {
                $query->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->first();
    }

    private function freshPairCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = strtoupper(Str::random(6));

            if (! MobileScannerSession::query()->where('pair_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'pair_code' => 'Could not create a unique mobile scanner pairing code. Please try again.',
        ]);
    }

    private function sessionPayload(Tenant $tenant, MobileScannerSession $session): array
    {
        return [
            'token' => $session->token,
            'pair_code' => $session->pair_code,
            'name' => $session->name,
            'expires_at' => $session->expires_at?->toIso8601String(),
            'mobile_url' => route('tenant.mobile-scanner.show', [$tenant, $session->token]),
            'poll_url' => route('tenant.mobile-scanner.sessions.poll', [$tenant, $session->token]),
            'close_url' => route('tenant.mobile-scanner.sessions.close', [$tenant, $session->token]),
        ];
    }
}
