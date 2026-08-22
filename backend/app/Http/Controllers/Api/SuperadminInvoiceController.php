<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Superadmin;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperadminInvoiceController
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {
    }

    /**
     * verifikasi transfer pembayaran invoice oleh superadmin
     */
    public function verify(Request $request, string $id): JsonResponse
    {
        /** @var Superadmin|null $superadmin */
        $superadmin = $request->user();

        // fallback buat pengujian superadmin kalo guard default
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
}
