<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $token,
        public readonly string $resetUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permintaan Pemulihan Kata Sandi Akun PRÉCIS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.reset-password',
            with: [
                'userName' => $this->user->name,
                'token' => $this->token,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
