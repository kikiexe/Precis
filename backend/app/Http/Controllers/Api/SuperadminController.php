<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Constants\DomainConstants;
use App\Http\Resources\SuperadminInvoiceResource;
use App\Http\Resources\TenantDirectoryResource;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Services\BillingService;
use App\Services\SuperadminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SuperadminController
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly SuperadminService $superadminService,
    ) {
    }

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

    public function me(Request $request): JsonResponse
    {
        /** @var Superadmin $superadmin */
        $superadmin = $request->user();

        if (! ($superadmin instanceof Superadmin)) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Endpoint ini memerlukan otorisasi Superadmin.',
            ], Response::HTTP_FORBIDDEN);
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

    public function logout(Request $request): JsonResponse
    {
        $superadmin = $request->user();
        if ($superadmin instanceof Superadmin) {
            $superadmin->currentAccessToken()?->delete();
        }

        return new JsonResponse([
            'message' => 'Sesi Superadmin telah diakhiri.',
        ], Response::HTTP_OK);
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = $this->superadminService->getInvoices($request->query('status'));

        return new JsonResponse([
            'message' => 'Daftar faktur berhasil dimuat.',
            'data' => SuperadminInvoiceResource::collection($invoices)->resolve(),
        ], Response::HTTP_OK);
    }

    public function verifyInvoice(Request $request, string $id): JsonResponse
    {
        /** @var Superadmin $superadmin */
        $superadmin = $request->user();

        if (! ($superadmin instanceof Superadmin)) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Endpoint ini memerlukan otorisasi Superadmin.',
            ], Response::HTTP_FORBIDDEN);
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

    public function metrics(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Metrik global SaaS berhasil dimuat.',
            'data' => $this->superadminService->getGlobalMetrics(),
        ], Response::HTTP_OK);
    }

    public function tenants(Request $request): JsonResponse
    {
        $tenants = $this->superadminService->getTenantsDirectory($request->query('status'));

        return new JsonResponse([
            'message' => 'Direktori tenant berhasil dimuat.',
            'data' => TenantDirectoryResource::collection($tenants)->resolve(),
        ], Response::HTTP_OK);
    }

    public function updateTenantStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:ACTIVE,SUSPENDED,GRACE_PERIOD,TRIAL'],
        ]);

        $user = $this->superadminService->updateTenantSubscriptionStatus($id, $request->input('status'));

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

    public function extendTenantSubscription(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:' . DomainConstants::MAX_SUBSCRIPTION_EXTENSION_DAYS],
        ]);

        $days = (int) ($request->input('days') ?? DomainConstants::DEFAULT_SUBSCRIPTION_EXTENSION_DAYS);
        $user = $this->superadminService->extendTenantSubscription($id, $days);

        return new JsonResponse([
            'message' => "Masa aktif langganan berhasil diperpanjang {$days} hari sampai {$user->subscription_expires_at?->toDateString()}.",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_status' => $user->subscription_status,
                'subscription_expires_at' => $user->subscription_expires_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::all()->map(fn (SubscriptionPlan $plan): array => [
            'id' => $plan->id,
            'name' => $plan->name,
            'max_workspaces' => $plan->max_workspaces,
            'monthly_price' => (float) $plan->monthly_price,
            'annual_price' => (float) $plan->annual_price,
            'is_active' => (bool) $plan->is_active,
        ])->toArray();

        return new JsonResponse([
            'message' => 'Daftar paket langganan berhasil dimuat.',
            'data' => $plans,
        ], Response::HTTP_OK);
    }
}
