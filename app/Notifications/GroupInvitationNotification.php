<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification
{
    public function __construct(private readonly Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'invitation',
            'title'   => 'Property Access Invitation',
            'message' => "You have been invited to access {$this->invitation->ownership->display_name} as {$this->invitation->role}.",
            'url'     => route('invitations.show', $this->invitation->token),
        ];
    }
}
