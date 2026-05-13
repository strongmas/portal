<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BuyerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $buyer,
        public string $tempPassword,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('Strongmas Residence Portal — Your Password Has Been Reset')
            ->view('emails.buyer-password-reset');
    }
}
