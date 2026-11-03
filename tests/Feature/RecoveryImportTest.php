<?php

namespace Tests\Feature;

use App\Mail\PropertyLinkedMail;
use App\Mail\StaffAlertMail;
use App\Mail\WelcomeBuyerMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Rebuilding the ledger after the production database was lost: the portal's
 * own emails are turned into CSVs (recovery:extract-emails), then loaded back
 * through the normal schedule logic (recovery:import).
 */
class RecoveryImportTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;
    private Property $elysian;
    private Property $kesbel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/recovery-test-' . uniqid();
        File::makeDirectory($this->dir);

        $this->elysian = Property::create(['code' => 'STR-ELY-014', 'development_name' => 'Elysian Rise', 'address' => 'Victoria Island']);
        $this->kesbel  = Property::create(['code' => 'STR-KES-007', 'development_name' => 'Kesbel Court', 'address' => 'Lekki']);

        PropertyUnit::create(['property_id' => $this->elysian->id, 'bedrooms' => 3, 'unit_type' => 'apartment', 'list_price_kobo' => 0, 'total_units' => 10]);
        PropertyUnit::create(['property_id' => $this->elysian->id, 'bedrooms' => 2, 'unit_type' => 'apartment', 'list_price_kobo' => 0, 'total_units' => 10]);
        PropertyUnit::create(['property_id' => $this->kesbel->id, 'bedrooms' => 3, 'unit_type' => 'apartment', 'list_price_kobo' => 0, 'total_units' => 5]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ledger_is_rebuilt_from_the_portals_own_emails(): void
    {
        $this->sendProductionEmails();
        $this->loseTheDatabase();

        $this->artisan('recovery:extract-emails', ['source' => "{$this->dir}/eml"])->assertSuccessful();

        $draft    = "{$this->dir}/eml/recovery-draft";
        $payments = $this->readCsv("{$draft}/payments.csv");

        // Two staff received each alert; each transfer leg is drafted once.
        $this->assertCount(3, $payments);
        $this->assertSame('2026-05-10', $payments[0]['paid_on']);

        // The property linked emails are read before the welcome emails; the
        // buyer's full name must still come through from the welcome.
        $this->assertSame(['Ada Obi', 'Emeka Eze'], array_column($this->readCsv("{$draft}/buyers.csv"), 'name'));

        $this->artisan('recovery:import', ['dir' => $draft, '--execute' => true])->assertSuccessful();

        $ada = User::where('email', 'ada@buyer.test')->firstOrFail();
        $ownership = $ada->ownerships()->with('instalments')->sole();
        $this->assertSame('3 Bedroom Apartment', $ownership->unit->label);
        $this->assertSame(1_000_000_000, (int) $ownership->agreed_price_kobo);
        $this->assertSame(250_000_000, $ownership->paid_kobo);
        $this->assertSame(
            ['paid', 'due', 'overdue', 'overdue'], // March–June plan, restored in October

            $ownership->instalments->sortBy('instalment_number')->pluck('status')->all(),
        );
        $this->assertSame(1_000_000_000, (int) $ownership->instalments->sum('amount_kobo'));

        $emeka   = User::where('email', 'emeka@buyer.test')->firstOrFail();
        $outright = $emeka->ownerships()->where('property_id', $this->elysian->id)->sole();
        $kesbel   = $emeka->ownerships()->where('property_id', $this->kesbel->id)->sole();
        $this->assertSame('completed', $outright->status);
        $this->assertSame(300_000_000, $kesbel->paid_kobo);

        $split = Payment::where('reference', 'TRF-777')->get();
        $this->assertCount(2, $split);
        $this->assertNotNull($split[0]->payment_group_id);
        $this->assertSame($split[0]->payment_group_id, $split[1]->payment_group_id);
        $this->assertNull($split[0]->proof_path);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->writeRecoveryCsvs();

        $this->artisan('recovery:import', ['dir' => $this->dir])
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertSame(0, User::count());
        $this->assertSame(0, Ownership::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_bad_rows_abort_the_whole_import(): void
    {
        $this->writeRecoveryCsvs(payments: [
            ['buyer_email' => 'ada@buyer.test', 'property' => 'Elysian Rise', 'unit' => '', 'amount' => '12000000', 'paid_on' => '2026-04-02', 'reference' => 'TRF-1'],
            ['buyer_email' => 'ada@buyer.test', 'property' => 'Nowhere Towers', 'unit' => '', 'amount' => '100', 'paid_on' => '2026-04-03', 'reference' => 'TRF-2'],
        ]);

        $this->artisan('recovery:import', ['dir' => $this->dir, '--execute' => true])
            ->expectsOutputToContain('Nothing was written')
            ->expectsOutputToContain('no property "Nowhere Towers"')
            ->expectsOutputToContain('payments total ₦12,000,000 but the agreed price is ₦10,000,000')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_running_twice_does_not_duplicate_anything(): void
    {
        $this->writeRecoveryCsvs();

        $this->artisan('recovery:import', ['dir' => $this->dir, '--execute' => true])->assertSuccessful();
        $this->artisan('recovery:import', ['dir' => $this->dir, '--execute' => true])->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertSame(1, Ownership::count());
        $this->assertSame(1, Payment::count());
        $this->assertSame(4, Instalment::count());
    }

    public function test_invites_go_only_to_buyers_created_by_the_import(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'already@buyer.test']);

        $this->writeRecoveryCsvs(buyers: [
            ['name' => 'Ada Obi', 'email' => 'ada@buyer.test'],
            ['name' => 'Already Here', 'email' => 'already@buyer.test'],
        ]);

        $this->artisan('recovery:import', ['dir' => $this->dir, '--execute' => true, '--send-invites' => true])->assertSuccessful();

        Mail::assertSent(WelcomeBuyerMail::class, fn ($mail) => $mail->hasTo('ada@buyer.test') && $mail->propertyName === 'Elysian Rise · 3 Bedroom Apartment');
        Mail::assertNotSent(WelcomeBuyerMail::class, fn ($mail) => $mail->hasTo('already@buyer.test'));
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Send the emails production would have sent — through the real mailables
     * and templates — and save each one as a .eml file, as a Zoho export would.
     */
    private function sendProductionEmails(): void
    {
        Carbon::setTestNow('2026-05-10 09:30:00');
        config(['mail.default' => 'array']);

        $unit = fn (Property $p, int $bedrooms) => $p->units()->where('bedrooms', $bedrooms)->value('id');

        $ada   = User::factory()->create(['name' => 'Ada Obi', 'email' => 'ada@buyer.test']);
        $emeka = User::factory()->create(['name' => 'Emeka Eze', 'email' => 'emeka@buyer.test']);

        $linked = [
            [$ada, $this->elysian, $unit($this->elysian, 3), '10000000', 'instalment', 4],
            [$emeka, $this->elysian, $unit($this->elysian, 2), '5000000', 'outright', 1],
            [$emeka, $this->kesbel, $unit($this->kesbel, 3), '6000000', 'instalment', 2],
        ];

        foreach ($linked as [$buyer, $property, $unitId, $price, $plan, $count]) {
            $ownership = Ownership::create([
                'user_id' => $buyer->id, 'property_id' => $property->id, 'property_unit_id' => $unitId,
                'agreed_price_kobo' => kobo($price), 'payment_plan' => $plan, 'total_instalments' => $count,
                'instalment_frequency' => 'monthly', 'start_date' => '2026-03-01', 'status' => 'active',
            ])->load(['property', 'unit']);

            Mail::to($buyer->email)->send(new PropertyLinkedMail($buyer, $ownership));
        }

        Mail::to($ada->email)->send(new WelcomeBuyerMail($ada, 'temp-pass', 'Elysian Rise · 3 Bedroom Apartment'));
        Mail::to($emeka->email)->send(new WelcomeBuyerMail($emeka, 'temp-pass', 'Elysian Rise · 2 Bedroom Apartment'));

        foreach (['finance@strongmas.test', 'admin@strongmas.test'] as $staff) {
            Mail::to($staff)->send(new StaffAlertMail(
                alertSubject: 'New payment submitted by Ada Obi',
                heading: 'Payment submitted for review',
                body: "Buyer: Ada Obi\nProperty: Elysian Rise\nAmount: ₦2,500,000\nReference: TRF-001",
            ));
            Mail::to($staff)->send(new StaffAlertMail(
                alertSubject: 'Split payment submitted by Emeka Eze',
                heading: 'Split payment submitted for review',
                body: "Buyer: Emeka Eze\nOne transfer of ₦8,000,000 split across 2 properties:\n"
                    . "  · Elysian Rise · 2 Bedroom Apartment: ₦5,000,000\n"
                    . "  · Kesbel Court · 3 Bedroom Apartment: ₦3,000,000\nReference: TRF-777",
            ));
        }

        File::makeDirectory("{$this->dir}/eml");
        foreach (app('mail.manager')->mailer('array')->getSymfonyTransport()->messages() as $i => $sent) {
            // Symfony stamps Date from the real clock, not Carbon's test clock.
            $raw = preg_replace('/^Date: .*$/m', 'Date: Sun, 10 May 2026 09:30:00 +0100', $sent->toString(), 1);
            file_put_contents("{$this->dir}/eml/{$i}.eml", $raw);
        }

        Carbon::setTestNow('2026-10-24 12:00:00');
    }

    private function loseTheDatabase(): void
    {
        Payment::query()->delete();
        Instalment::query()->delete();
        Ownership::query()->delete();
        User::query()->delete();
    }

    private function writeRecoveryCsvs(?array $buyers = null, ?array $payments = null): void
    {
        $this->writeCsv('buyers.csv', $buyers ?? [['name' => 'Ada Obi', 'email' => 'ada@buyer.test']]);
        $this->writeCsv('ownerships.csv', [[
            'buyer_email' => 'ada@buyer.test', 'property' => 'STR-ELY-014', 'unit' => '3 Bedroom Apartment',
            'agreed_price' => '10,000,000', 'payment_plan' => 'instalment', 'instalment_frequency' => 'monthly',
            'total_instalments' => '4', 'start_date' => '2026-03-01',
        ]]);
        $this->writeCsv('payments.csv', $payments ?? [[
            'buyer_email' => 'ada@buyer.test', 'property' => 'Elysian Rise', 'unit' => '',
            'amount' => '2500000', 'paid_on' => '2026-03-02', 'reference' => 'TRF-001',
        ]]);
    }

    private function writeCsv(string $name, array $rows): void
    {
        $handle = fopen("{$this->dir}/{$name}", 'w');
        fputcsv($handle, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);
    }

    private function readCsv(string $path): array
    {
        $rows   = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES));
        $header = array_shift($rows);

        return array_map(fn ($row) => array_combine($header, $row), $rows);
    }
}
