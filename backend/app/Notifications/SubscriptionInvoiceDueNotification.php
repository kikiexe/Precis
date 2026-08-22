<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionInvoiceDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tagihan Perpanjangan Langganan Précis - ' . $this->invoice->invoice_number)
            ->greeting('Halo ' . ($notifiable->name ?? 'Pelanggan') . ',')
            ->line('Masa aktif langganan workspace Anda akan segera berakhir dalam 3 hari.')
            ->line('Nomor Faktur: ' . $this->invoice->invoice_number)
            ->line('Total Pembayaran: Rp ' . number_format((float) $this->invoice->total_amount, 0, ',', '.'))
            ->line('Kode Unik: ' . $this->invoice->unique_code)
            ->line('Jatuh Tempo: ' . ($this->invoice->due_date ? $this->invoice->due_date->format('d M Y') : '-'))
            ->line('Mohon lakukan transfer sesuai total pembayaran untuk verifikasi otomatis.')
            ->action('Lihat Faktur Tagihan', url('/billing/invoices'))
            ->line('Terima kasih telah mempercayai Précis untuk operasional bisnis Anda.');
    }
}
