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

        $root = storage_path('app/private');
        $this->info('Private storage root');
        $this->line("  {$root}");
        $this->line('  exists:   ' . (is_dir($root) ? 'yes' : 'NO'));
        $this->line('  writable: ' . (is_writable($root) ? 'yes' : 'NO'));
        if (!is_dir($root) || !is_writable($root)) {
            $ok = false;
            $this->error('  → uploads cannot be saved. Create the directory and make it writable by the web server user.');
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
        }
        $this->newLine();

        $this->info('Payment proof records');
        $payments = Payment::orderBy('id')->get(['id', 'user_id', 'proof_path']);
        $missingPath = $payments->filter(fn ($p) => blank($p->proof_path));
        $missingFile = $payments->filter(
            fn ($p) => filled($p->proof_path) && !Storage::disk('private')->exists($p->proof_path)
        );

        $this->line("  payments total:            {$payments->count()}");
        $this->line("  with no proof_path stored: {$missingPath->count()}" . ($missingPath->isEmpty() ? '' : ' → ids ' . $missingPath->pluck('id')->implode(', ')));
        $this->line("  proof_path set, file gone: {$missingFile->count()}" . ($missingFile->isEmpty() ? '' : ' → ids ' . $missingFile->pluck('id')->implode(', ')));

        if ($missingFile->isNotEmpty()) {
            $ok = false;
            $this->error('  → the database points at files that are not on this server (lost in a deploy, or written to a different storage path).');
        }
        $this->newLine();

        $orphans = collect(Storage::disk('private')->allFiles('payment-proofs'))
            ->diff($payments->pluck('proof_path')->filter());
        $this->line('  proof files on disk with no payment row: ' . $orphans->count());

        $this->newLine();
        $ok ? $this->info('All checks passed.') : $this->error('Problems found — see above.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
