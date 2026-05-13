<?php

namespace App\Mail;

use App\Models\InterestRegistration;
use App\Models\SalesRep;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterestForwarded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InterestRegistration $interest,
        public SalesRep $rep,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('New Buyer Interest — ' . $this->interest->property->development_name . ' — Strongmas Residence')
            ->view('emails.interest-forwarded');
    }
}
