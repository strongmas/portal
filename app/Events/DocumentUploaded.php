<?php

namespace App\Events;

use App\Models\PropertyDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PropertyDocument $document) {}
}
