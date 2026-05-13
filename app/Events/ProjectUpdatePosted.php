<?php

namespace App\Events;

use App\Models\ProjectUpdate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectUpdatePosted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ProjectUpdate $update) {}
}
