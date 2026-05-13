<?php

namespace App\Listeners;

use App\Events\ProjectUpdatePosted;
use App\Mail\ProjectUpdateMail;
use App\Notifications\ProjectUpdateNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendProjectUpdateNotifications
{
    public function handle(ProjectUpdatePosted $event): void
    {
        $update = $event->update->load(['property.ownerships.user', 'property.ownerships.members.user']);

        foreach ($update->property->ownerships as $ownership) {
            $usersToNotify = collect([$ownership->user]);

            foreach ($ownership->members as $member) {
                if ($member->accepted_at) {
                    $usersToNotify->push($member->user);
                }
            }

            foreach ($usersToNotify->unique('id') as $user) {
                Mail::to($user->email)->send(new ProjectUpdateMail($update));
                $user->notify(new ProjectUpdateNotification($update));
            }
        }
    }
}
