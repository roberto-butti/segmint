<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public OrganizationInvitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invitation to {$this->invitation->organization->name}")
            ->line("{$this->invitation->invitedBy->name} invited you to join {$this->invitation->organization->name} as {$this->invitation->role->label()}.")
            ->action('Review invitation', $this->acceptUrl())
            ->line('The invitation expires in seven days.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'organization_name' => $this->invitation->organization->name,
            'role' => $this->invitation->role->value,
            'url' => $this->acceptUrl(),
        ];
    }

    private function acceptUrl(): string
    {
        return URL::temporarySignedRoute(
            'invitations.show',
            $this->invitation->expires_at,
            ['invitation' => $this->invitation],
        );
    }
}
