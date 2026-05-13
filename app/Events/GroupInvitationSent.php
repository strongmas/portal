<?php

namespace App\Events;

use App\Models\Invitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupInvitationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Invitation $invitation) {}
}
