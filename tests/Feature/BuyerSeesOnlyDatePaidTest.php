<?php

namespace Tests\Feature;

use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerSeesOnlyDatePaidTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): Payment
    {
        $buyer = User::factory()->create();
        $property = Property::create(['code' => 'STR-' . uniqid(), 'development_name' => 'Test Estate', 'address' => 'Lagos']);

        $ownership = Ownership::create([
            'user_id' => $buyer->id,
            'property_id' => $property->id,
            'agreed_price_kobo' => 100_000_000,
            'payment_plan' => 'outright',
            'total_instalments' => 1,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'ownership_id' => $ownership->id,
            'user_id' => $buyer->id,
            'amount_kobo' => 5_000_000,
            'paid_on' => '2026-03-14',
            'reference' => 'REF-1',
        ]);

        // Logged long after it was paid.
        $payment->forceFill(['created_at' => '2026-10-29 16:45:00'])->saveQuietly();

        return $payment->fresh();
    }

    public function test_payment_history_shows_date_paid_but_not_date_logged(): void
    {
        $payment = $this->payment();

        $this->actingAs($payment->user)->get(route('payments.history'))
            ->assertOk()
            ->assertSee('14 Mar 2026')
            ->assertDontSee('29 Oct')
            ->assertDontSee('16:45');
    }

    public function test_receipt_shows_date_paid_but_not_date_logged(): void
    {
        $payment = $this->payment();

        $html = view('pdf.receipt', compact('payment'))->render();

        $this->assertStringContainsString('14 March 2026', $html);
        $this->assertStringNotContainsString('Date logged', $html);
        $this->assertStringNotContainsString('29 October 2026', $html);
        $this->assertStringNotContainsString('16:45', $html);
    }
}
