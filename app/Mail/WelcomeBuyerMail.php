<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeBuyerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $buyer,
        public string $tempPassword,
        public ?string $propertyName = null,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('Welcome to Strongmas Residence Portal — Your Account Details')
            ->view('emails.welcome-buyer');
    }
}
