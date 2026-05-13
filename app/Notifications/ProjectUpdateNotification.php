<?php

namespace App\Notifications;

use App\Models\ProjectUpdate;
use Illuminate\Notifications\Notification;

class ProjectUpdateNotification extends Notification
{
    public function __construct(private readonly ProjectUpdate $update) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'project_update',
            'title'     => 'New Project Update',
            'message'   => "{$this->update->property->development_name}: {$this->update->title}",
            'url'       => route('project-updates.show', $this->update),
            'update_id' => $this->update->id,
        ];
    }
}
