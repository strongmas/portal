<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The schedule must always reconcile: paid slots sum to what was actually paid,
 * unpaid slots sum to what is still owed, and together they equal the agreed price.
 */
class InstalmentScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function buildOwnership(int $agreedKobo, array $paymentsKobo, int $instalmentCount): Ownership
    {
        $user = User::factory()->create();

        $property = Property::create([
            'code'             => 'TST-' . uniqid(),
            'development_name' => 'Test Development',
            'address'          => '1 Test Road',
        ]);

        $ownership = Ownership::create([
            'user_id'                  => $user->id,
            'property_id'              => $property->id,
            'agreed_price_kobo'        => $agreedKobo,
            'start_date'               => '2026-01-01',
            'expected_completion_date' => '2027-01-01',
            'payment_plan'             => 'instalment',
            'total_instalments'        => $instalmentCount,
            'instalment_frequency'     => 'monthly',
            'status'                   => 'active',
        ]);

        for ($i = 1; $i <= $instalmentCount; $i++) {
            Instalment::create([
                'ownership_id'      => $ownership->id,
                'instalment_number' => $i,
                'amount_kobo'       => intdiv($agreedKobo, $instalmentCount),
                'due_date'          => now()->addMonths($i)->toDateString(),
                'status'            => 'upcoming',
            ]);
        }

        foreach ($paymentsKobo as $n => $amount) {
            Payment::create([
                'ownership_id' => $ownership->id,
                'user_id'      => $user->id,
                'amount_kobo'  => $amount,
                'paid_on'      => now()->subMonths(count($paymentsKobo) - $n)->toDateString(),
                'reference'    => 'REF-' . $n . '-' . uniqid(),
            ]);
        }

        app(PaymentController::class)->recalculateSchedule($ownership->fresh());

        return $ownership->fresh();
    }

    private function assertReconciles(Ownership $ownership): void
    {
        $instalments = $ownership->instalments()->orderBy('instalment_number')->get();
        $paidSlots   = (int) $instalments->where('status', 'paid')->sum('amount_kobo');
        $unpaidSlots = (int) $instalments->where('status', '!=', 'paid')->sum('amount_kobo');

        $this->assertSame(
            (int) $ownership->paid_kobo, $paidSlots,
            'Paid instalment slots must sum to the total actually paid'
        );
        $this->assertSame(
            (int) $ownership->outstanding_kobo, $unpaidSlots,
            'Unpaid instalment slots must sum to the outstanding balance'
        );
        $this->assertSame(
            (int) $ownership->agreed_price_kobo, $paidSlots + $unpaidSlots,
            'The whole schedule must sum to the agreed price'
        );
    }

    public static function scheduleCases(): array
    {
        return [
            'two of twelve paid'            => [12_000_000, array_fill(0, 2, 1_000_000), 12],
            'several small part-payments'   => [12_000_000, array_fill(0, 4, 100_000), 12],
            'as many payments as slots'     => [12_000_000, array_fill(0, 12, 100_000), 12],
            'more payments than slots'      => [12_000_000, array_fill(0, 15, 100_000), 12],
            'paid off in one payment'       => [12_000_000, [12_000_000], 12],
            'paid off over many payments'   => [12_000_000, array_fill(0, 15, 800_000), 12],
            'amount not evenly divisible'   => [10_000_001, [1], 3],
            'no payments yet'               => [12_000_000, [], 12],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("scheduleCases")]
    public function test_schedule_reconciles_to_the_agreed_price(int $agreed, array $payments, int $slots): void
    {
        $this->assertReconciles($this->buildOwnership($agreed, $payments, $slots));
    }

    public function test_outstanding_balance_stays_visible_when_payments_fill_every_slot(): void
    {
        // Regression: 12 payments against a 12-slot plan used to mark every
        // instalment paid, hiding the remaining debt and leaving no next due row.
        $ownership = $this->buildOwnership(12_000_000, array_fill(0, 12, 100_000), 12);

        $this->assertGreaterThan(0, $ownership->outstanding_kobo);
        $this->assertNotNull(
            $ownership->next_due_instalment,
            'A buyer who still owes money must always have a next due instalment'
        );
        $this->assertSame('active', $ownership->status);
        $this->assertReconciles($ownership);
    }

    public function test_ownership_completes_only_when_fully_paid(): void
    {
        $paid = $this->buildOwnership(12_000_000, [12_000_000], 12);
        $this->assertSame(0, $paid->outstanding_kobo);
        $this->assertSame('completed', $paid->status);

        $short = $this->buildOwnership(12_000_000, [11_999_999], 12);
        $this->assertSame('active', $short->status);
        $this->assertSame(1, $short->outstanding_kobo);
    }
}
