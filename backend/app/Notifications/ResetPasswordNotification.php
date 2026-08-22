<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly string $resetUrl,
    ) {
    }

    /**
     * cara pengiriman notifikasi
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * email notifikasi
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Permintaan Pemulihan Kata Sandi Akun Précis')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.')
            ->action('Atur Ulang Kata Sandi', $this->resetUrl)
            ->line('Token pemulihan Anda: ' . $this->token)
            ->line('Tautan dan token ini akan kedaluwarsa dalam 60 menit.')
            ->line('Jika Anda tidak merasa meminta pengaturan ulang kata sandi, abaikan email ini.');
    }
}
