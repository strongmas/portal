<?php

namespace Tests\Feature;

use App\Mail\StaffAlertMail;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.backups.bucket' => 'offsite-backups',
            'filesystems.disks.backups.key'    => 'key',
            'filesystems.disks.backups.secret' => 'secret',
            'backup.alert_email'               => 'ops@strongmas.test',
        ]);

        Storage::fake('backups');
        Storage::fake('private');
        Storage::fake('public');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dump_restores_the_ledger_exactly(): void
    {
        $payment = $this->makePayment("O'Brien & Sons — ₦ transfer");

        $this->artisan('backup:run')->assertSuccessful();

        $dumps = Storage::disk('backups')->files('db');
        $this->assertCount(1, $dumps);

        $sql = gzdecode(Storage::disk('backups')->get($dumps[0]));
        $this->assertStringContainsString('INSERT INTO "payments"', $sql);
        $this->assertStringNotContainsString('INSERT INTO "sessions"', $sql);

        // Replay into a brand-new, empty database — what a real restore does.
        config(['database.connections.restore' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::connection('restore')->unprepared($sql);

        $restored = DB::connection('restore')->table('payments')->find($payment->id);
        $this->assertSame("O'Brien & Sons — ₦ transfer", $restored->notes);
        $this->assertSame((int) $payment->amount_kobo, (int) $restored->amount_kobo);
        $this->assertSame($payment->ownership_id, (int) $restored->ownership_id);
        $this->assertSame(1, DB::connection('restore')->table('ownerships')->count());
    }

    public function test_uploads_are_copied_once_and_never_deleted_from_the_backup(): void
    {
        Storage::disk('private')->put('payment-proofs/1/proof.pdf', 'proof');
        Storage::disk('public')->put('property-images/1/front.jpg', 'image');

        $this->artisan('backup:run')->expectsOutputToContain('2 new or changed file(s) copied')->assertSuccessful();
        $this->artisan('backup:run')->expectsOutputToContain('0 new or changed file(s) copied')->assertSuccessful();

        Storage::disk('private')->delete('payment-proofs/1/proof.pdf');
        $this->artisan('backup:run')->assertSuccessful();

        Storage::disk('backups')->assertExists('files/private/payment-proofs/1/proof.pdf');
        Storage::disk('backups')->assertExists('files/public/property-images/1/front.jpg');
    }

    public function test_old_dumps_are_pruned_but_monthly_ones_kept_for_a_year(): void
    {
        $backups = Storage::disk('backups');
        foreach (['2025-09-01', '2026-02-01', '2026-02-09', '2026-09-03', '2026-09-20', '2026-10-20'] as $day) {
            $backups->put("db/{$day}_020000.sql.gz", 'old');
        }

        Carbon::setTestNow('2026-10-24 02:00:00');
        $this->artisan('backup:run --only-db')->assertSuccessful();

        $backups->assertMissing('db/2025-09-01_020000.sql.gz'); // monthly, but over a year old
        $backups->assertExists('db/2026-02-01_020000.sql.gz');  // first of its month
        $backups->assertMissing('db/2026-02-09_020000.sql.gz'); // ordinary daily, past 30 days
        $backups->assertExists('db/2026-09-03_020000.sql.gz');  // first of September
        $backups->assertMissing('db/2026-09-20_020000.sql.gz');
        $backups->assertExists('db/2026-10-20_020000.sql.gz');  // within 30 days
        $backups->assertExists('db/2026-10-24_020000.sql.gz');  // tonight's
    }

    public function test_missing_destination_fails_loudly(): void
    {
        config(['filesystems.disks.backups.bucket' => null]);

        $this->artisan('backup:run')->expectsOutputToContain('No backup destination')->assertFailed();

        Mail::assertSent(StaffAlertMail::class, fn ($mail) => $mail->hasTo('ops@strongmas.test') && str_contains($mail->alertSubject, 'Backup FAILED'));
    }

    public function test_refuses_to_back_up_into_the_apps_own_bucket(): void
    {
        config([
            'filesystems.disks.private' => ['driver' => 's3', 'bucket' => 'offsite-backups', 'endpoint' => null],
            'filesystems.disks.backups.endpoint' => null,
        ]);

        $this->artisan('backup:run')->expectsOutputToContain('same bucket the app stores its private files in')->assertFailed();
    }

    public function test_weekly_report_is_emailed(): void
    {
        $this->artisan('backup:run --report')->assertSuccessful();

        Mail::assertSent(StaffAlertMail::class, fn ($mail) => str_contains($mail->alertSubject, 'Weekly backup report'));
    }

    private function makePayment(string $notes): Payment
    {
        $user      = User::factory()->create();
        $property  = Property::create(['code' => 'STR-ELY-014', 'development_name' => 'Elysian Rise', 'address' => 'VI']);
        $ownership = Ownership::create([
            'user_id' => $user->id, 'property_id' => $property->id, 'agreed_price_kobo' => 1_000_000_000,
            'payment_plan' => 'outright', 'total_instalments' => 1, 'start_date' => '2026-03-01', 'status' => 'active',
        ]);

        return Payment::create([
            'ownership_id' => $ownership->id, 'user_id' => $user->id, 'amount_kobo' => 250_000_000,
            'paid_on' => '2026-03-02', 'reference' => 'TRF-001', 'notes' => $notes,
        ]);
    }
}
