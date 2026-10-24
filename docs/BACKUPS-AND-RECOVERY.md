# Backups and recovery

On 14 September 2026 Laravel Cloud deleted the portal's database and both file
buckets after a billing lapse. There was no backup, and everything lived with one
provider, so everything went at once. This runbook exists so that can't happen again.

## Backups

`php artisan backup:run` runs nightly at 02:00 (see `routes/console.php`):

- **Database** → gzipped SQL dump at `db/YYYY-MM-DD_HHMMSS.sql.gz` on the `backups` disk.
  Cache, session and queue tables are saved as empty tables.
- **Uploaded files** → every file on the `private` and `public` disks is copied to
  `files/private/…` and `files/public/…`. Files are only ever added to the backup, never removed.
- **Retention** → daily dumps for 30 days (`BACKUP_KEEP_DAYS`), plus the first dump of
  each month for 12 months.
- **Alerts** → any failure emails `BACKUP_ALERT_EMAIL`. The Monday run also emails a
  summary. **If the Monday email stops arriving, the backups have stopped.**

### Setup

1. Create a bucket with a **different provider or account** from the app's storage
   (Cloudflare R2 or Backblaze B2 under a separate login both work). The command refuses
   to back up into the app's own bucket.
2. Set `BACKUP_BUCKET`, `BACKUP_ACCESS_KEY_ID`, `BACKUP_SECRET_ACCESS_KEY`, `BACKUP_ENDPOINT`
   and `BACKUP_ALERT_EMAIL` in the environment.
3. Make sure the scheduler runs: on Laravel Cloud, enable the scheduler on the app; on cPanel,
   add the cron job `* * * * * cd /path/to/portal && php artisan schedule:run >> /dev/null 2>&1`.
4. Run `php artisan backup:run --report` once by hand and confirm the email arrives.

### Restoring

1. Download the latest `db/….sql.gz` from the backup bucket.
2. `gunzip -c dump.sql.gz | mysql -u USER -p DATABASE` (or import it in phpMyAdmin).
3. Copy `files/private/` and `files/public/` back to the app's private and public disks,
   keeping the same paths.
4. `php artisan schedules:reconcile` and `php artisan uploads:doctor` to confirm the ledger
   and the files line up.

## Rebuilding without a backup (the 2026 recovery)

The portal emails most of its records as it goes: welcome emails and "property linked"
emails to buyers, and payment alerts to staff. Those emails are the source for rebuilding.

1. Export the relevant mail folders from Zoho as `.eml` files into one folder.
2. `php artisan recovery:extract-emails <folder>` drafts `buyers.csv`, `ownerships.csv`
   and `payments.csv` in `<folder>/recovery-draft`.
3. Work through the `check` column. Replace each payment's `paid_on` (the date the alert
   was sent) with the transfer date from the bank statement, and confirm each plan's
   instalment frequency.
4. `php artisan recovery:import <folder>/recovery-draft` (dry run), then add `--execute`.
   Add `--send-invites` when buyers should be emailed a temporary password.

Restored payments have no proof file; `uploads:doctor` lists them so buyers can re-submit.
