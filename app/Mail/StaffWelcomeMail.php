<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $staff,
        public string $password,
        public bool $isReset = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReset
            ? 'Your Strongmas Admin password has been reset'
            : 'Your Strongmas Admin account is ready';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-welcome');
    }
}
