<?php

namespace App\Mail;

use App\Models\Ownership;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PropertyLinkedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $buyer,
        public Ownership $ownership,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('A New Property Has Been Linked to Your Account — Strongmas Residence')
            ->view('emails.property-linked');
    }
}
