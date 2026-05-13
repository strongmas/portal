<?php

namespace App\Notifications;

use App\Models\PropertyDocument;
use Illuminate\Notifications\Notification;

class DocumentUploadedNotification extends Notification
{
    public function __construct(private readonly PropertyDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'document',
            'title'       => 'New Document Available',
            'message'     => "\"{$this->document->title}\" has been uploaded for {$this->document->property->development_name}.",
            'url'         => route('documents.index'),
            'document_id' => $this->document->id,
        ];
    }
}
