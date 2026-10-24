<?php

namespace App\Console\Commands;

use App\Mail\StaffAlertMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Off-site backup: a gzipped SQL dump of the database plus a copy of every
 * file on the private and public disks, all written to the "backups" disk.
 *
 * Written in plain PHP rather than shelling out to mysqldump, so it runs the
 * same on Laravel Cloud, cPanel and locally. Uploaded files are copied once
 * and never deleted from the backup, so a file removed by mistake (or by a
 * provider) can still be recovered.
 *
 * Dumps are kept for backup.keep_days days, plus the first dump of each month
 * for twelve months. Any failure emails backup.alert_email.
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run
        {--only-db : Skip copying uploaded files}
        {--report : Email a summary of the backups held, even if nothing failed}';

    protected $description = 'Back up the database and uploaded files to the off-site "backups" disk';

    /** Rebuilt by the app on demand — their rows aren't worth keeping. */
    private const SCHEMA_ONLY = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];

    private const FILE_DISKS = ['private', 'public'];

    public function handle(): int
    {
        try {
            $this->checkDestination();

            $dump = $this->dumpDatabase();

            $copied = 0;
            if (!$this->option('only-db')) {
                foreach (self::FILE_DISKS as $disk) {
                    $copied += $this->mirrorDisk($disk);
                }
            }

            $pruned = $this->pruneDumps();
        } catch (\Throwable $e) {
            report($e);
            $this->error('Backup FAILED: ' . $e->getMessage());
            $this->sendAlert('Backup FAILED', "The nightly backup did not complete.\n\nError: {$e->getMessage()}\n\nUntil this is fixed, new payments and uploads are not being backed up anywhere.");

            return self::FAILURE;
        }

        $this->info("Database dumped to {$dump['path']} (" . $this->size($dump['bytes']) . ", {$dump['rows']} rows)");
        $this->info("{$copied} new or changed file(s) copied; {$pruned} old dump(s) pruned");

        if ($this->option('report')) {
            $this->sendReport($dump);
        }

        return self::SUCCESS;
    }

    private function checkDestination(): void
    {
        $backups = config('filesystems.disks.backups');

        if ($backups['driver'] === 's3' && (!$backups['bucket'] || !$backups['key'] || !$backups['secret'])) {
            throw new \RuntimeException('No backup destination. Set BACKUP_BUCKET, BACKUP_ACCESS_KEY_ID and BACKUP_SECRET_ACCESS_KEY (and BACKUP_ENDPOINT for R2/B2).');
        }

        // A backup sitting next to the data dies with it.
        foreach (self::FILE_DISKS as $disk) {
            $config = config("filesystems.disks.{$disk}");
            if (($config['driver'] ?? null) === 's3'
                && $config['bucket'] === ($backups['bucket'] ?? null)
                && ($config['endpoint'] ?? null) === ($backups['endpoint'] ?? null)) {
                throw new \RuntimeException("BACKUP_BUCKET is the same bucket the app stores its {$disk} files in. Backups must live somewhere else.");
            }
        }
    }

    // ── Database ───────────────────────────────────────────────────────────

    /** @return array{path: string, bytes: int, rows: int} */
    private function dumpDatabase(): array
    {
        $local = tempnam(sys_get_temp_dir(), 'dump');
        $gz    = gzopen($local, 'wb6');
        $rows  = 0;
        $db    = DB::connection();
        $pdo   = $db->getPdo();
        $mysql = in_array($db->getDriverName(), ['mysql', 'mariadb'], true);

        $write = fn (string $sql) => gzwrite($gz, $sql);

        $write('-- Strongmas portal backup, ' . now()->toDateTimeString() . ' (' . $db->getDatabaseName() . ")\n");
        $write($mysql ? "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n" : "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n");

        // Laravel 12 lists every schema on the server unless told which one.
        foreach (Schema::getTables($mysql ? $db->getDatabaseName() : 'main') as $table) {
            $name   = $table['name'];
            $quoted = $mysql ? "`{$name}`" : "\"{$name}\"";

            $write("DROP TABLE IF EXISTS {$quoted};\n");
            $write($this->createStatement($name, $mysql) . ";\n");

            if (in_array($name, self::SCHEMA_ONLY, true)) {
                $write("\n");
                continue;
            }

            $batch = [];
            foreach ($db->table($name)->orderBy($db->raw('1'))->cursor() as $row) {
                $values  = array_map(fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), (array) $row);
                $batch[] = '(' . implode(',', $values) . ')';
                $rows++;

                if (count($batch) === 200) {
                    $write("INSERT INTO {$quoted} VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                $write("INSERT INTO {$quoted} VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            $write("\n");
        }

        $write($mysql ? "SET FOREIGN_KEY_CHECKS=1;\n" : "COMMIT;\n");
        gzclose($gz);

        $path  = 'db/' . now()->format('Y-m-d_His') . '.sql.gz';
        $bytes = filesize($local);

        $stream = fopen($local, 'rb');
        Storage::disk('backups')->writeStream($path, $stream);
        fclose($stream);
        unlink($local);

        if (Storage::disk('backups')->size($path) !== $bytes) {
            throw new \RuntimeException("Uploaded dump {$path} does not match the local copy ({$bytes} bytes).");
        }

        return ['path' => $path, 'bytes' => $bytes, 'rows' => $rows];
    }

    private function createStatement(string $table, bool $mysql): string
    {
        if ($mysql) {
            return ((array) DB::selectOne("SHOW CREATE TABLE `{$table}`"))['Create Table'];
        }

        return DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table])->sql;
    }

    // ── Files ──────────────────────────────────────────────────────────────

    /** Copy anything new or changed. Nothing is ever deleted from the backup. */
    private function mirrorDisk(string $disk): int
    {
        $source  = Storage::disk($disk);
        $backups = Storage::disk('backups');
        $copied  = 0;

        foreach ($source->allFiles() as $file) {
            if (str_ends_with($file, '.gitignore')) {
                continue;
            }

            $target = "files/{$disk}/{$file}";
            if ($backups->exists($target) && $backups->size($target) === $source->size($file)) {
                continue;
            }

            $stream = $source->readStream($file);
            $backups->writeStream($target, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $copied++;
        }

        return $copied;
    }

    // ── Retention ──────────────────────────────────────────────────────────

    private function pruneDumps(): int
    {
        $keepDays = (int) config('backup.keep_days');
        $backups  = Storage::disk('backups');
        $dumps    = collect($backups->files('db'))
            ->mapWithKeys(fn ($path) => [$path => $this->dumpDate($path)])
            ->filter()
            ->sort();

        // The first dump of each month is kept for a year.
        $monthly = $dumps->groupBy(fn ($date) => $date->format('Y-m'), preserveKeys: true)
            ->map(fn ($month) => $month->keys()->first())
            ->values()
            ->all();

        $doomed = $dumps->filter(function ($date, $path) use ($keepDays, $monthly) {
            if ($date->gte(now()->subDays($keepDays))) {
                return false;
            }

            return !(in_array($path, $monthly, true) && $date->gte(now()->subMonths(12)));
        })->keys();

        // Never prune down to nothing, whatever the dates say.
        if ($doomed->count() >= $dumps->count()) {
            $doomed = $doomed->slice(0, -1);
        }

        $backups->delete($doomed->all());

        return $doomed->count();
    }

    private function dumpDate(string $path): ?Carbon
    {
        return preg_match('#(\d{4}-\d{2}-\d{2}_\d{6})\.sql\.gz$#', $path, $m)
            ? Carbon::createFromFormat('Y-m-d_His', $m[1])
            : null;
    }

    // ── Reporting ──────────────────────────────────────────────────────────

    private function sendReport(array $latest): void
    {
        $backups = Storage::disk('backups');
        $dumps   = $backups->files('db');
        $files   = count($backups->allFiles('files'));

        $this->sendAlert(
            'Weekly backup report',
            "Backups are running.\n\nLatest database dump: {$latest['path']} (" . $this->size($latest['bytes']) . ", {$latest['rows']} rows)\n"
                . 'Database dumps held: ' . count($dumps) . "\nUploaded files held: {$files}\n\n"
                . "If this email ever stops arriving on Mondays, the backups have stopped.",
        );
    }

    private function sendAlert(string $subject, string $body): void
    {
        $to = config('backup.alert_email');

        if (!$to) {
            Log::critical("{$subject}: {$body}");

            return;
        }

        try {
            Mail::to($to)->send(new StaffAlertMail(
                alertSubject: "[Strongmas portal] {$subject}",
                heading: $subject,
                body: $body,
            ));
        } catch (\Throwable $e) {
            // The mail server being down mustn't hide the original problem.
            Log::critical("{$subject}: {$body} (alert email failed: {$e->getMessage()})");
        }
    }

    private function size(int $bytes): string
    {
        return $bytes >= 1_048_576 ? round($bytes / 1_048_576, 1) . ' MB' : round($bytes / 1024, 1) . ' KB';
    }
}
