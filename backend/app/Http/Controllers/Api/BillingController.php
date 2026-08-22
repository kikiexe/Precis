<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Billing\CreateInvoiceRequest;
use App\Http\Requests\Billing\SubmitPaymentProofRequest;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingController
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {
    }

    /**
     * Buat faktur tagihan invoice paket langganan baru.
     */
    public function createInvoice(CreateInvoiceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $invoice = $this->billingService->createInvoice(
            user: $user,
            planId: (string) $request->validated('plan_id'),
            billingCycle: (string) ($request->validated('billing_cycle') ?? 'MONTHLY'),
        );

        return new JsonResponse([
            'message' => 'Faktur tagihan langganan berhasil dibuat.',
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount_base' => (float) $invoice->amount_base,
                'unique_code' => $invoice->unique_code,
                'total_amount' => (float) $invoice->total_amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Ambil riwayat faktur tagihan invoice milik user yang login.
     */
    public function myInvoices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $list = $this->billingService->getUserInvoices($user);

        return new JsonResponse([
            'message' => 'Riwayat faktur tagihan berhasil dimuat.',
            'data' => $list,
        ], Response::HTTP_OK);
    }

    /**
     * Unggah bukti transfer pembayaran invoice oleh user.
     */
    public function submitProof(SubmitPaymentProofRequest $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $confirmation = $this->billingService->submitPaymentProof(
            user: $user,
            invoiceId: $id,
            bankAccountName: (string) $request->validated('bank_account_name'),
            transferAmount: (float) $request->validated('transfer_amount'),
            proofImageUrl: (string) $request->validated('proof_image_url'),
        );

        return new JsonResponse([
            'message' => 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi.',
            'data' => [
                'id' => $confirmation->id,
                'invoice_id' => $confirmation->invoice_id,
                'bank_account_name' => $confirmation->bank_account_name,
                'transfer_amount' => (float) $confirmation->transfer_amount,
                'proof_image_url' => $confirmation->proof_image_url,
            ],
        ], Response::HTTP_CREATED);
    }
}
