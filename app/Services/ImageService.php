<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Compress and resize an uploaded image, store it on the given disk, and return the stored path.
     * Falls back to plain file storage if image processing fails (missing GD/Imagick).
     *
     * @param int $maxWidth  Scale down proportionally if the image exceeds this width.
     * @param int $quality   JPEG / WebP quality (1–100).
     */
    public static function store(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        int $maxWidth = 1920,
        int $quality = 82
    ): string {
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = Str::random(40);

        try {
            $image = Image::read($file->getPathname());

            // Only scale down, never up
            if ($image->width() > $maxWidth) {
                $image->scaleDown(width: $maxWidth);
            }

            // Encode to JPEG for jpg/jpeg/png; keep webp as webp
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $encoded = $image->toJpeg($quality);
                $path    = $directory . '/' . $name . '.jpg';
            } else {
                $encoded = $image->toWebp($quality);
                $path    = $directory . '/' . $name . '.webp';
            }

            Storage::disk($disk)->put($path, (string) $encoded);

            return $path;
        } catch (\Throwable) {
            // Driver not available (e.g. no GD/Imagick) – fall back to raw store
            return $file->storeAs($directory, $name . '.' . $ext, $disk);
        }
    }
}
