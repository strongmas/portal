<?php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Mail\DocumentUploadedMail;
use App\Notifications\DocumentUploadedNotification;
use App\Models\Ownership;
use Illuminate\Support\Facades\Mail;

class SendDocumentUploadedNotifications
{
    public function handle(DocumentUploaded $event): void
    {
        $document  = $event->document->load('property');
        $property  = $document->property;

        $ownerships = Ownership::where('property_id', $property->id)
            ->with(['user', 'members.user'])
            ->get();

        foreach ($ownerships as $ownership) {
            $users = collect([$ownership->user]);
            foreach ($ownership->members as $member) {
                if ($member->accepted_at) $users->push($member->user);
            }
            foreach ($users->unique('id') as $user) {
                Mail::to($user->email)->send(new DocumentUploadedMail($document));
                $user->notify(new DocumentUploadedNotification($document));
            }
        }
    }
}
