<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

/**
 * Copy sensitive files out of the shared (public) bucket into a dedicated
 * private one. Copies only — the source objects are left untouched, so the
 * running app keeps serving proofs until AWS_PRIVATE_BUCKET is switched over.
 */
class MigratePrivateFiles extends Command
{
    protected $signature = 'storage:migrate-private
        {--dest-bucket= : Destination bucket (default: AWS_PRIVATE_BUCKET)}
        {--dest-key= : Destination access key id (default: AWS_PRIVATE_ACCESS_KEY_ID)}
        {--dest-secret= : Destination secret (default: AWS_PRIVATE_SECRET_ACCESS_KEY)}
        {--prefix=* : Prefixes to copy (default: payment-proofs, property-documents, support-attachments)}
        {--execute : Actually copy. Without this the command only reports what it would do}';

    protected $description = 'Copy payment proofs, property documents and support attachments into a private bucket';

    public function handle(): int
    {
        $sourceBucket = config('filesystems.disks.public.bucket');
        $destBucket   = $this->option('dest-bucket') ?: env('AWS_PRIVATE_BUCKET');
        $destKey      = $this->option('dest-key') ?: env('AWS_PRIVATE_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'));
        $destSecret   = $this->option('dest-secret') ?: env('AWS_PRIVATE_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'));

        $prefixes = $this->option('prefix') ?: ['payment-proofs', 'property-documents', 'support-attachments'];

        if (!$sourceBucket) {
            $this->error('No source bucket. AWS_BUCKET must be set.');

            return self::FAILURE;
        }

        if (!$destBucket || !$destKey || !$destSecret) {
            $this->error('Destination not configured. Pass --dest-bucket, --dest-key and --dest-secret,');
            $this->error('or set AWS_PRIVATE_BUCKET / AWS_PRIVATE_ACCESS_KEY_ID / AWS_PRIVATE_SECRET_ACCESS_KEY.');

            return self::FAILURE;
        }

        if ($sourceBucket === $destBucket) {
            $this->error("Source and destination are the same bucket ({$sourceBucket}). Nothing to do.");

            return self::FAILURE;
        }

        $execute = $this->option('execute');

        $this->info(($execute ? 'Copying' : 'DRY RUN — would copy') . " from {$sourceBucket} to {$destBucket}");
        $this->line('  prefixes: ' . implode(', ', $prefixes));
        $this->line('  source objects are never deleted');
        $this->newLine();

        $source = $this->client(
            config('filesystems.disks.public.key'),
            config('filesystems.disks.public.secret')
        );
        $dest = $this->client($destKey, $destSecret);

        // Prove we can actually write to the destination before reporting on
        // thousands of objects. Without this, bad credentials are indistinguishable
        // from an empty destination — every object just looks like it needs copying.
        $probe = '_migrate-probe/' . uniqid() . '.txt';

        try {
            $dest->putObject(['Bucket' => $destBucket, 'Key' => $probe, 'Body' => 'preflight']);
            $dest->deleteObject(['Bucket' => $destBucket, 'Key' => $probe]);
            $this->line('  destination write access: ok');
        } catch (\Throwable $e) {
            $this->error('  destination is not writable: ' . explode("\n", $e->getMessage())[0]);
            $this->error('  Check --dest-bucket, --dest-key and --dest-secret (are they still placeholders?).');

            return self::FAILURE;
        }

        $this->newLine();

        $copied = $skipped = $failed = 0;

        foreach ($prefixes as $prefix) {
            $this->line("── {$prefix}");

            try {
                $pages = $source->getPaginator('ListObjectsV2', [
                    'Bucket' => $sourceBucket,
                    'Prefix' => rtrim($prefix, '/') . '/',
                ]);
            } catch (\Throwable $e) {
                $this->error('   cannot list: ' . $e->getMessage());
                $failed++;

                continue;
            }

            foreach ($pages as $page) {
                foreach ($page['Contents'] ?? [] as $object) {
                    $key  = $object['Key'];
                    $size = (int) $object['Size'];

                    // Already there and the same size? Leave it — makes re-runs safe.
                    try {
                        $existing = $dest->headObject(['Bucket' => $destBucket, 'Key' => $key]);
                        if ((int) $existing['ContentLength'] === $size) {
                            $skipped++;

                            continue;
                        }
                    } catch (\Throwable $e) {
                        // Not present in the destination — fall through and copy it.
                    }

                    if (!$execute) {
                        $this->line("   would copy {$key} ({$size} bytes)");
                        $copied++;

                        continue;
                    }

                    try {
                        $dest->copyObject([
                            'Bucket'     => $destBucket,
                            'Key'        => $key,
                            'CopySource' => "{$sourceBucket}/" . rawurlencode($key),
                        ]);

                        // Verify rather than trust the API's success response.
                        $check = $dest->headObject(['Bucket' => $destBucket, 'Key' => $key]);
                        if ((int) $check['ContentLength'] !== $size) {
                            throw new \RuntimeException("size mismatch: {$check['ContentLength']} != {$size}");
                        }

                        $copied++;
                        $this->line("   copied {$key}");
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->error("   FAILED {$key}: " . explode("\n", $e->getMessage())[0]);
                    }
                }
            }
        }

        $this->newLine();
        $this->line(($execute ? 'Copied: ' : 'Would copy: ') . $copied);
        $this->line('Already present, skipped: ' . $skipped);

        if ($failed) {
            $this->error("Failed: {$failed}");
            $this->error('Do NOT switch AWS_PRIVATE_BUCKET until every object copies cleanly.');

            return self::FAILURE;
        }

        $this->info('No failures.');

        if (!$execute) {
            $this->newLine();
            $this->line('Re-run with --execute to perform the copy.');
        } else {
            $this->newLine();
            $this->line('Now set AWS_PRIVATE_BUCKET (and its key/secret) and redeploy.');
            $this->line('Verify with: php artisan uploads:doctor');
        }

        return self::SUCCESS;
    }

    private function client(?string $key, ?string $secret): S3Client
    {
        return new S3Client([
            'version'                 => 'latest',
            'region'                  => config('filesystems.disks.public.region') ?: 'auto',
            'endpoint'                => config('filesystems.disks.public.endpoint'),
            'use_path_style_endpoint' => (bool) config('filesystems.disks.public.use_path_style_endpoint', false),
            'credentials'             => ['key' => $key, 'secret' => $secret],
        ]);
    }
}
