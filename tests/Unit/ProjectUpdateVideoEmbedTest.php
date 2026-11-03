<?php

namespace Tests\Unit;

use App\Models\ProjectUpdate;
use PHPUnit\Framework\TestCase;

class ProjectUpdateVideoEmbedTest extends TestCase
{
    public function test_cloudinary_player_links_are_embedded_as_given(): void
    {
        $url = 'https://player.cloudinary.com/embed/?cloud_name=bz58htco&public_id=Aurum_update';

        $this->assertSame($url, (new ProjectUpdate(['youtube_url' => $url]))->youtube_embed_url);
    }

    public function test_youtube_links_still_embed(): void
    {
        $update = new ProjectUpdate(['youtube_url' => 'https://youtu.be/abc123']);

        $this->assertSame('https://www.youtube.com/embed/abc123', $update->youtube_embed_url);
    }

    public function test_other_links_are_not_embedded(): void
    {
        $update = new ProjectUpdate(['youtube_url' => 'https://example.com/video.mp4']);

        $this->assertNull($update->youtube_embed_url);
    }
}
