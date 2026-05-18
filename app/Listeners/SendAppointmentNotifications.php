<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Mail\AppointmentBookedAdminMail;
use App\Mail\AppointmentBookedRepMail;
use App\Notifications\NewAppointmentNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendAppointmentNotifications
{
    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment->load(['user', 'property', 'salesRep']);

        // Notify admins with 'appointments' permission via email + in-app
        $admins = User::withAdminPermission('appointments')->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new AppointmentBookedAdminMail($appointment));
            $admin->notify(new NewAppointmentNotification($appointment));
        }

        // Notify assigned sales rep via email
        if ($appointment->salesRep) {
            Mail::to($appointment->salesRep->email)
                ->send(new AppointmentBookedRepMail($appointment));
        }

        // Notify the buyer via in-app
        $appointment->user->notify(new NewAppointmentNotification($appointment, true));
    }
}
