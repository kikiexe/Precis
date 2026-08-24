<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkspaceInvitationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly WorkspaceInvitation $invitation,
        public readonly string $inviteUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $workspaceName = $this->invitation->workspace?->name ?? 'PRÉCIS Workspace';

        return new Envelope(
            subject: "Undangan Bergabung ke Tim {$workspaceName} di PRÉCIS",
        );
    }

    public function content(): Content
    {
        $workspace = $this->invitation->workspace;
        $inviter = $this->invitation->invitedBy;
        $branch = $this->invitation->branch;

        return new Content(
            view: 'emails.workspace.invitation',
            with: [
                'workspaceName' => $workspace?->name ?? 'Workspace',
                'inviterName' => $inviter?->name ?? 'Pemilik Workspace',
                'jobTitle' => $this->invitation->job_title,
                'role' => $this->invitation->role,
                'branchName' => $branch?->name,
                'inviteUrl' => $this->inviteUrl,
                'expiresAt' => $this->invitation->expires_at?->translatedFormat('d M Y, H:i') ?? $this->invitation->expires_at?->format('d M Y, H:i') . ' WIB',
            ],
        );
    }
}
