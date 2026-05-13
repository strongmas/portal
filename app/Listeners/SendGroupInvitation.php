<?php

namespace App\Listeners;

use App\Events\GroupInvitationSent;
use App\Mail\GroupInvitationMail;
use Illuminate\Support\Facades\Mail;

class SendGroupInvitation
{
    public function handle(GroupInvitationSent $event): void
    {
        Mail::to($event->invitation->email)
            ->send(new GroupInvitationMail($event->invitation));
    }
}
