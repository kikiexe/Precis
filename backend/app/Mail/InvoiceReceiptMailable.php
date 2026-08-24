<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReceiptMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $owner,
        public readonly Invoice $invoice,
        public readonly string $billingUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Kuitansi Pembayaran Langganan PRÉCIS #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        $expiry = $this->owner->subscription_expires_at;
        $activeUntil = $expiry ? $expiry->format('d M Y') : 'Aktif';

        return new Content(
            view: 'emails.billing.invoice-receipt',
            with: [
                'ownerName' => $this->owner->name,
                'invoiceNumber' => $this->invoice->invoice_number,
                'totalAmount' => (float) $this->invoice->total_amount,
                'activeUntil' => $activeUntil,
                'billingUrl' => $this->billingUrl,
            ],
        );
    }
}
