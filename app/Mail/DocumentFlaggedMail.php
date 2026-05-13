<?php

namespace App\Mail;

use App\Models\PropertyDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentFlaggedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly PropertyDocument $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Document Re-upload — ' . $this->document->property->development_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document-flagged');
    }
}
