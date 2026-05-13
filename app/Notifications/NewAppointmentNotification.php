<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;

class NewAppointmentNotification extends Notification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly bool $isBuyer = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $property = $this->appointment->property->development_name;

        return [
            'type'    => 'appointment',
            'title'   => $this->isBuyer
                ? 'Inspection Requested'
                : 'New Inspection Booking',
            'message' => $this->isBuyer
                ? "Your inspection for {$property} on {$this->appointment->preferred_date->format('d M Y')} is pending confirmation."
                : "{$this->appointment->user->name} booked an inspection for {$property} on {$this->appointment->preferred_date->format('d M Y')}.",
            'url'     => $this->isBuyer
                ? route('appointments.index')
                : route('admin.appointments.show', $this->appointment),
            'appointment_id' => $this->appointment->id,
        ];
    }
}
