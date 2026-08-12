<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UploadsDoctor extends Command
{
    protected $signature = 'uploads:doctor';

    protected $description = 'Check that private file uploads (payment proofs, documents, attachments) can actually be written and read on this server';

    public function handle(): int
    {
        $ok = true;

        $this->info('PHP upload limits');
        foreach (['file_uploads', 'upload_max_filesize', 'post_max_size', 'max_file_uploads', 'memory_limit'] as $key) {
            $this->line("  {$key} = " . ini_get($key));
        }
        $this->line('  (proof validation allows 5 MB — upload_max_filesize AND post_max_size must both exceed that)');
        $this->newLine();

        // Which backend the "private" disk actually resolves to. Setting AWS_BUCKET
        // switches it to S3/R2, so the local directory below becomes irrelevant.
        $driver = config('filesystems.disks.private.driver');
        $this->info('Private disk backend');
        $this->line("  driver: {$driver}");

        if ($driver === 's3') {
            $this->line('  bucket:   ' . (config('filesystems.disks.private.bucket') ?: '(not set)'));
            $this->line('  region:   ' . (config('filesystems.disks.private.region') ?: '(not set)'));
            $this->line('  endpoint: ' . (config('filesystems.disks.private.endpoint') ?: '(default AWS)'));
            $this->line('  key id:   ' . (config('filesystems.disks.private.key')
                ? substr((string) config('filesystems.disks.private.key'), 0, 6) . '… (set)'
                : 'NOT SET'));
            $this->line('  secret:   ' . (config('filesystems.disks.private.secret') ? '(set)' : 'NOT SET'));
            $this->line('  Uploads go to object storage — local directory permissions do not apply.');

            // Proofs and public images must not share a bucket: anything reachable
            // over the public URL bypasses the app's authorisation checks.
            if (config('filesystems.disks.private.bucket') === config('filesystems.disks.public.bucket')) {
                $ok = false;
                $this->newLine();
                $this->error('  → Payment proofs share a bucket with public images.');
                $this->error('    If that bucket is public, proof documents are readable by URL without');
                $this->error('    logging in. Create a separate non-public bucket and set AWS_PRIVATE_BUCKET.');
            }
            $this->newLine();

            // A cached config keeps whatever credentials were present at build
            // time. If the platform has since rotated them, the running app is
            // still signing requests with the old key — which reads as a 401
            // even though the dashboard shows correct values.
            $this->line('  config cached: ' . (app()->configurationIsCached() ? 'YES' : 'no'));

            foreach (['AWS_ACCESS_KEY_ID' => 'key', 'AWS_SECRET_ACCESS_KEY' => 'secret', 'AWS_BUCKET' => 'bucket'] as $var => $configKey) {
                $live   = getenv($var) ?: null;
                $inUse  = config("filesystems.disks.private.{$configKey}");

                if ($live !== null && $inUse !== null && $live !== $inUse) {
                    $ok = false;
                    $this->error("  → {$var} in the environment does NOT match the value the app is using.");
                    $this->error('    The config cache is stale. Redeploy, or run: php artisan config:clear');
                }
            }
        } else {
            $root = storage_path('app/private');
            $this->line("  root:     {$root}");
            $this->line('  exists:   ' . (is_dir($root) ? 'yes' : 'NO'));
            $this->line('  writable: ' . (is_writable($root) ? 'yes' : 'NO'));
            if (!is_dir($root) || !is_writable($root)) {
                $ok = false;
                $this->error('  → uploads cannot be saved. Create the directory and make it writable by the web server user.');
            }

            // On containerised hosting the local disk does not survive a deploy.
            // Writes succeed, so nothing looks wrong until the files are gone.
            if (app()->environment('production')) {
                $ok = false;
                $this->newLine();
                $this->error('  → DANGER: production is storing uploads on the local disk.');
                $this->error('    AWS_BUCKET is unset, so the private disk fell back to local storage.');
                $this->error('    On container hosting (Laravel Cloud, Docker, Fly, Heroku) this directory is');
                $this->error('    wiped on every deploy and is not shared between instances. Uploads will');
                $this->error('    appear to succeed and then disappear.');
                $this->error('    Attach object storage and set AWS_BUCKET before accepting more uploads.');
            }
        }
        $this->newLine();

        $this->info('Write / read / delete round-trip on the "private" disk');
        $probe = 'payment-proofs/_doctor/' . uniqid() . '.txt';
        try {
            Storage::disk('private')->put($probe, 'uploads:doctor probe');
            $this->line('  write:  ok');
            $this->line('  exists: ' . (Storage::disk('private')->exists($probe) ? 'ok' : 'FAILED'));
            $this->line('  read:   ' . (Storage::disk('private')->get($probe) === 'uploads:doctor probe' ? 'ok' : 'FAILED'));
            Storage::disk('private')->delete($probe);
            $this->line('  delete: ok');
        } catch (\Throwable $e) {
            $ok = false;
            $this->error('  FAILED: ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'Unauthorized') || str_contains($e->getMessage(), '401')) {
                $this->newLine();
                $this->warn('  A 401 on every operation means the credentials themselves are rejected.');
                $this->warn('  It is not a bucket policy or a path problem. Check, in order:');
                $this->warn('   1. Custom AWS_* environment variables overriding the platform-injected ones');
                $this->warn('      — remove the custom entries so the injected values win.');
                $this->warn('   2. The storage token has been rotated or revoked — re-issue it and redeploy.');
                $this->warn('   3. The bucket belongs to a different environment than this one.');
            }
        }
        $this->newLine();

        $this->info('Payment proof records');
        $payments    = Payment::orderBy('id')->get(['id', 'user_id', 'proof_path']);
        $missingPath = $payments->filter(fn ($p) => blank($p->proof_path));
        $missingFile = collect();
        $uncheckable = collect();

        foreach ($payments->filter(fn ($p) => filled($p->proof_path)) as $payment) {
            try {
                if (!Storage::disk('private')->exists($payment->proof_path)) {
                    $missingFile->push($payment);
                }
            } catch (\Throwable $e) {
                // Storage unreachable — keep going so the rest of the report still prints.
                $uncheckable->push($payment);
            }
        }

        $this->line("  payments total:            {$payments->count()}");
        $this->line("  with no proof_path stored: {$missingPath->count()}" . ($missingPath->isEmpty() ? '' : ' → ids ' . $missingPath->pluck('id')->implode(', ')));
        $this->line("  proof_path set, file gone: {$missingFile->count()}" . ($missingFile->isEmpty() ? '' : ' → ids ' . $missingFile->pluck('id')->implode(', ')));

        if ($uncheckable->isNotEmpty()) {
            $ok = false;
            $this->error("  could not be checked at all:  {$uncheckable->count()} → storage is unreachable (see the round-trip failure above)");
        }

        if ($missingPath->isNotEmpty()) {
            $this->warn('  → payments with no proof_path were saved while uploads were failing silently. Ask those buyers to re-submit the proof.');
        }

        if ($missingFile->isNotEmpty()) {
            $ok = false;
            $this->error('  → the database points at files that are not in storage (lost in a deploy, or written to a different backend).');
            $this->newLine();
            $this->line('  These buyers must re-submit their proof:');

            foreach (Payment::with(['user', 'ownership.property'])->whereIn('id', $missingFile->pluck('id'))->get() as $payment) {
                $this->line(sprintf(
                    '    #%d  %s <%s>  %s  paid %s  ref %s',
                    $payment->id,
                    $payment->user->name ?? 'unknown buyer',
                    $payment->user->email ?? '—',
                    naira($payment->amount_kobo),
                    $payment->paid_on?->format('d M Y') ?? '—',
                    $payment->reference ?: '—'
                ));
            }
        }
        $this->newLine();

        try {
            $orphans = collect(Storage::disk('private')->allFiles('payment-proofs'))
                ->diff($payments->pluck('proof_path')->filter());
            $this->line('  proof files in storage with no payment row: ' . $orphans->count());
        } catch (\Throwable $e) {
            $ok = false;
            $this->error('  could not list stored proofs: ' . $e->getMessage());
        }

        $this->newLine();
        $ok ? $this->info('All checks passed.') : $this->error('Problems found — see above.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
