<?php

namespace App\Console\Commands;

use App\Models\ProjectUpdate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredVideos extends Command
{
    protected $signature = 'videos:purge-expired';

    protected $description = 'Delete expired property walkthrough videos and clear their records';

    public function handle(): int
    {
        $expired = ProjectUpdate::whereNotNull('video_path')
            ->where('video_expires_at', '<', now())
            ->get();

        foreach ($expired as $update) {
            Storage::disk('public')->delete($update->video_path);
            $update->update(['video_path' => null, 'video_expires_at' => null]);
        }

        $count = $expired->count();
        $this->info("Purged $count expired video(s).");

        return Command::SUCCESS;
    }
}
