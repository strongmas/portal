<?php

namespace App\Mail;

use App\Models\ProjectUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ProjectUpdate $update) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Project Update — ' . $this->update->property->development_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project-update');
    }
}
