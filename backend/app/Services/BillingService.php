<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\InvoiceReceiptMailable;
use App\Models\Invoice;
use App\Models\PaymentConfirmation;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BillingService
{
    /**
     * buat invoice tagihan paket langganan
     */
    public function createInvoice(User $user, string $planId, string $billingCycle = 'MONTHLY'): Invoice
    {
        /** @var SubscriptionPlan $plan */
        $plan = SubscriptionPlan::findOrFail($planId);

        $amountBase = strtoupper($billingCycle) === 'ANNUAL'
            ? (float) $plan->annual_price
            : (float) $plan->monthly_price;

        // generator kode unik 3 digit (100 sampai 999)
        $uniqueCode = random_int(100, 999);
        $totalAmount = $amountBase + $uniqueCode;

        $invoiceNumber = sprintf('INV-%s-%s', date('Ymd'), strtoupper(Str::random(6)));
        $dueDate = Carbon::now()->addDays(3);

        return Invoice::create([
            'user_id' => $user->id,
            'invoice_number' => $invoiceNumber,
            'amount_base' => $amountBase,
            'unique_code' => $uniqueCode,
            'total_amount' => $totalAmount,
            'due_date' => $dueDate,
            'status' => 'UNPAID',
        ]);
    }

    /**
     * upload bukti transfer pembayaran oleh user
     */
    public function submitPaymentProof(
        User $user,
        string $invoiceId,
        string $bankAccountName,
        float $transferAmount,
        string $proofImageUrl
    ): PaymentConfirmation {
        /** @var Invoice|null $invoice */
        $invoice = Invoice::where('id', $invoiceId)
            ->where('user_id', $user->id)
            ->first();

        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice_id' => ['Faktur tagihan tidak ditemukan atau bukan milik Anda.'],
            ]);
        }

        if ($invoice->status === 'PAID') {
            throw ValidationException::withMessages([
                'invoice' => ['Faktur tagihan ini sudah dibayar dan terverifikasi.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $user, $bankAccountName, $transferAmount, $proofImageUrl): PaymentConfirmation {
            $confirmation = PaymentConfirmation::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'user_id' => $user->id,
                    'bank_account_name' => $bankAccountName,
                    'transfer_amount' => $transferAmount,
                    'proof_image_url' => $proofImageUrl,
                ]
            );

            $invoice->update(['status' => 'PENDING_VERIFICATION']);

            return $confirmation;
        });
    }

    /**
     * verifikasi pembayaran invoice oleh superadmin dan perpanjang masa aktif akun owner
     */
    public function verifyInvoicePayment(Superadmin $superadmin, string $invoiceId): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::with(['confirmation', 'user'])->findOrFail($invoiceId);

        return DB::transaction(function () use ($invoice, $superadmin): Invoice {
            $now = Carbon::now();

            if ($invoice->confirmation) {
                $invoice->confirmation->update([
                    'verified_by_superadmin_id' => $superadmin->id,
                    'verified_at' => $now,
                ]);
            }

            $invoice->update(['status' => 'PAID']);

            // perpanjang masa aktif langganan user
            $user = $invoice->user;
            if ($user) {
                $currentExpiry = $user->subscription_expires_at;
                $isCurrentlyActive = $currentExpiry && Carbon::parse($currentExpiry)->isFuture();

                // tambahkan 30 hari dari tanggal kedaluwarsa saat ini atau dari waktu sekarang
                $newExpiry = $isCurrentlyActive
                    ? Carbon::parse($currentExpiry)->addDays(30)
                    : $now->copy()->addDays(30);

                $user->update([
                    'subscription_status' => 'ACTIVE',
                    'subscription_expires_at' => $newExpiry,
                ]);

                $billingUrl = config('app.url') . '/settings/billing';
                Mail::to($user->email)->send(new InvoiceReceiptMailable($user, $invoice, $billingUrl));
            }

            return $invoice;
        });
    }

    /**
     * ambil riwayat invoice tagihan milik user yang login
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserInvoices(User $user): array
    {
        return Invoice::with('confirmation')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Invoice $invoice): array {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount_base' => (float) $invoice->amount_base,
                    'unique_code' => $invoice->unique_code,
                    'total_amount' => (float) $invoice->total_amount,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date?->toIso8601String(),
                    'created_at' => $invoice->created_at?->toIso8601String(),
                    'confirmation' => $invoice->confirmation ? [
                        'bank_account_name' => $invoice->confirmation->bank_account_name,
                        'transfer_amount' => (float) $invoice->confirmation->transfer_amount,
                        'proof_image_url' => $invoice->confirmation->proof_image_url,
                        'verified_at' => $invoice->confirmation->verified_at?->toIso8601String(),
                    ] : null,
                ];
            })
            ->toArray();
    }
}
