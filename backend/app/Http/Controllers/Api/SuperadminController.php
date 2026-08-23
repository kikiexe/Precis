<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SuperadminController
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {
    }

    /**
     * autentikasi superadmin dan penerbitan token akses sanctum terisolasi
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var Superadmin|null $superadmin */
        $superadmin = Superadmin::where('email', $request->input('email'))->first();

        if (! $superadmin || ! Hash::check($request->input('password'), $superadmin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial Superadmin tidak valid.'],
            ]);
        }

        $token = $superadmin->createToken('superadmin_portal_token')->plainTextToken;

        return new JsonResponse([
            'message' => 'Autentikasi Superadmin berhasil.',
            'data' => [
                'token' => $token,
                'superadmin' => [
                    'id' => $superadmin->id,
                    'name' => $superadmin->name,
                    'email' => $superadmin->email,
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ambil data identitas superadmin yang sedang login
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Superadmin|null $superadmin */
        $superadmin = $request->user();

        if (! ($superadmin instanceof Superadmin)) {
            $superadmin = Superadmin::firstOrFail();
        }

        return new JsonResponse([
            'message' => 'Profil Superadmin berhasil dimuat.',
            'data' => [
                'id' => $superadmin->id,
                'name' => $superadmin->name,
                'email' => $superadmin->email,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * revoke token sesi superadmin aktif
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var Superadmin|null $superadmin */
        $superadmin = $request->user();

        if ($superadmin instanceof Superadmin) {
            $superadmin->currentAccessToken()?->delete();
        }

        return new JsonResponse([
            'message' => 'Sesi Superadmin telah diakhiri.',
        ], Response::HTTP_OK);
    }

    /**
     * ambil daftar semua faktur tagihan untuk verifikasi pembayaran
     */
    public function invoices(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Invoice::with(['user.workspaces', 'confirmation'])
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['UNPAID', 'PENDING_VERIFICATION', 'PAID', 'EXPIRED'], true)) {
            $query->where('status', $status);
        }

        $invoices = $query->get()->map(function (Invoice $invoice): array {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount_base' => (float) $invoice->amount_base,
                'unique_code' => $invoice->unique_code,
                'total_amount' => (float) $invoice->total_amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toIso8601String(),
                'created_at' => $invoice->created_at?->toIso8601String(),
                'user' => $invoice->user ? [
                    'id' => $invoice->user->id,
                    'name' => $invoice->user->name,
                    'email' => $invoice->user->email,
                    'subscription_status' => $invoice->user->subscription_status,
                    'workspaces' => $invoice->user->workspaces->map(function ($ws) {
                        return [
                            'id' => $ws->id,
                            'name' => $ws->name,
                            'slug' => $ws->slug,
                        ];
                    })->toArray(),
                ] : null,
                'confirmation' => $invoice->confirmation ? [
                    'id' => $invoice->confirmation->id,
                    'bank_account_name' => $invoice->confirmation->bank_account_name,
                    'transfer_amount' => (float) $invoice->confirmation->transfer_amount,
                    'proof_image_url' => $invoice->confirmation->proof_image_url,
                    'verified_at' => $invoice->confirmation->verified_at?->toIso8601String(),
                    'created_at' => $invoice->confirmation->created_at?->toIso8601String(),
                ] : null,
            ];
        })->toArray();

        return new JsonResponse([
            'message' => 'Daftar faktur berhasil dimuat.',
            'data' => $invoices,
        ], Response::HTTP_OK);
    }

    /**
     * verifikasi pembayaran invoice oleh superadmin dan aktifkan langganan owner
     */
    public function verifyInvoice(Request $request, string $id): JsonResponse
    {
        /** @var Superadmin|null $superadmin */
        $superadmin = $request->user();

        if (! ($superadmin instanceof Superadmin)) {
            $superadmin = Superadmin::firstOrFail();
        }

        $invoice = $this->billingService->verifyInvoicePayment(
            superadmin: $superadmin,
            invoiceId: $id,
        );

        return new JsonResponse([
            'message' => 'Pembayaran invoice berhasil diverifikasi dan langganan telah diperpanjang.',
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'user_id' => $invoice->user_id,
                'subscription_status' => $invoice->user?->subscription_status,
                'subscription_expires_at' => $invoice->user?->subscription_expires_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ringkasan metrik global saas untuk dashboard superadmin
     */
    public function metrics(): JsonResponse
    {
        $now = Carbon::now();

        // hitung total tenant berdasarkan status langganan
        $activeTenants = User::where('subscription_status', 'ACTIVE')->count();
        $gracePeriodTenants = User::where('subscription_status', 'GRACE_PERIOD')->count();
        $suspendedTenants = User::where('subscription_status', 'SUSPENDED')->count();
        $trialTenants = User::where('subscription_status', 'TRIAL')->count();
        $totalTenants = User::count();

        // total outlet cabang
        $totalBranches = Branch::count();

        // pending invoices needing review
        $pendingInvoicesCount = Invoice::where('status', 'PENDING_VERIFICATION')->count();
        $unpaidInvoicesCount = Invoice::where('status', 'UNPAID')->count();
        $paidInvoicesCount = Invoice::where('status', 'PAID')->count();

        // total pendapatan yang sudah dibayar (gross revenue)
        $totalRevenue = (float) Invoice::where('status', 'PAID')->sum('total_amount');

        // kalkulasi mrr (monthly recurring revenue) dari estimasi pengguna aktif & paket aktif
        $averagePlanPrice = (float) (SubscriptionPlan::where('is_active', true)->avg('monthly_price') ?? 150000.00);
        $estimatedMrr = $activeTenants * $averagePlanPrice;
        $estimatedArr = $estimatedMrr * 12;

        return new JsonResponse([
            'message' => 'Metrik global SaaS berhasil dimuat.',
            'data' => [
                'mrr' => $estimatedMrr,
                'arr' => $estimatedArr,
                'total_revenue' => $totalRevenue,
                'tenants' => [
                    'total' => $totalTenants,
                    'active' => $activeTenants,
                    'grace_period' => $gracePeriodTenants,
                    'suspended' => $suspendedTenants,
                    'trial' => $trialTenants,
                ],
                'total_branches' => $totalBranches,
                'invoices' => [
                    'pending' => $pendingInvoicesCount,
                    'unpaid' => $unpaidInvoicesCount,
                    'paid' => $paidInvoicesCount,
                ],
                'timestamp' => $now->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ambil direktori tenant/owner beserta data workspace & langganan
     */
    public function tenants(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = User::with(['workspaces.branches'])
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['ACTIVE', 'GRACE_PERIOD', 'SUSPENDED', 'TRIAL'], true)) {
            $query->where('subscription_status', $status);
        }

        $tenants = $query->get()->map(function (User $user): array {
            $now = Carbon::now();
            $expiresAt = $user->subscription_expires_at ? Carbon::parse($user->subscription_expires_at) : null;
            $daysRemaining = $expiresAt ? (int) $now->diffInDays($expiresAt, false) : null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_status' => $user->subscription_status,
                'subscription_expires_at' => $user->subscription_expires_at?->toIso8601String(),
                'days_remaining' => $daysRemaining,
                'max_workspaces' => $user->max_workspaces,
                'created_at' => $user->created_at?->toIso8601String(),
                'workspaces' => $user->workspaces->map(function ($ws) {
                    return [
                        'id' => $ws->id,
                        'name' => $ws->name,
                        'slug' => $ws->slug,
                        'status' => $ws->status,
                        'branches_count' => $ws->branches->count(),
                        'branches' => $ws->branches->map(function (Branch $branch) {
                            return [
                                'id' => $branch->id,
                                'name' => $branch->name,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();

        return new JsonResponse([
            'message' => 'Direktori tenant berhasil dimuat.',
            'data' => $tenants,
        ], Response::HTTP_OK);
    }

    /**
     * perbarui status langganan tenant secara manual (misal: suspended atau active)
     */
    public function updateTenantStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:ACTIVE,SUSPENDED,GRACE_PERIOD,TRIAL'],
        ]);

        /** @var User $user */
        $user = User::findOrFail($id);
        $user->update([
            'subscription_status' => $request->input('status'),
        ]);

        return new JsonResponse([
            'message' => "Status langganan tenant berhasil diperbarui menjadi {$user->subscription_status}.",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_status' => $user->subscription_status,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * perpanjang masa aktif langganan tenant secara manual (misal: +30 hari)
     */
    public function extendTenantSubscription(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $days = (int) ($request->input('days') ?? 30);

        /** @var User $user */
        $user = User::findOrFail($id);

        $now = Carbon::now();
        $currentExpiry = $user->subscription_expires_at ? Carbon::parse($user->subscription_expires_at) : null;
        $baseDate = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : $now;
        $newExpiry = $baseDate->copy()->addDays($days);

        $user->update([
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => $newExpiry,
        ]);

        return new JsonResponse([
            'message' => "Masa aktif langganan berhasil diperpanjang {$days} hari sampai {$newExpiry->toDateString()}.",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_status' => $user->subscription_status,
                'subscription_expires_at' => $user->subscription_expires_at->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ambil daftar paket langganan master
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::all()->map(function (SubscriptionPlan $plan): array {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'max_workspaces' => $plan->max_workspaces,
                'monthly_price' => (float) $plan->monthly_price,
                'annual_price' => (float) $plan->annual_price,
                'is_active' => (bool) $plan->is_active,
            ];
        })->toArray();

        return new JsonResponse([
            'message' => 'Daftar paket langganan berhasil dimuat.',
            'data' => $plans,
        ], Response::HTTP_OK);
    }
}
