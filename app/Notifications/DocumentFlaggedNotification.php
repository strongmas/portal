<?php

namespace App\Notifications;

use App\Models\PropertyDocument;
use Illuminate\Notifications\Notification;

class DocumentFlaggedNotification extends Notification
{
    public function __construct(private readonly PropertyDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'document_flagged',
            'title'       => 'Document Flagged for Re-upload',
            'message'     => "Your document \"{$this->document->title}\" for {$this->document->property->development_name} requires a correction. Please re-upload the correct document.",
            'url'         => route('documents.index'),
            'document_id' => $this->document->id,
        ];
    }
}
