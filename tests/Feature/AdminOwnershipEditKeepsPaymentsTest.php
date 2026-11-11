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

class AdminOwnershipEditKeepsPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function ownershipWithPayments(): Ownership
    {
        $buyer = User::factory()->create();
        $property = Property::create(['code' => 'STR-' . uniqid(), 'development_name' => 'Elysian Rise', 'address' => 'Lagos']);

        $ownership = Ownership::create([
            'user_id' => $buyer->id,
            'property_id' => $property->id,
            'agreed_price_kobo' => 60_000_000_000,
            'payment_plan' => 'instalment',
            'instalment_frequency' => 'quarterly',
            'total_instalments' => 8,
            'start_date' => now()->subYear(),
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 8; $i++) {
            Instalment::create([
                'ownership_id' => $ownership->id,
                'instalment_number' => $i,
                'due_date' => now()->subYear()->addMonths(($i - 1) * 3)->toDateString(),
                'amount_kobo' => 7_500_000_000,
                'status' => 'upcoming',
            ]);
        }

        foreach ([1, 2] as $n) {
            Payment::create([
                'ownership_id' => $ownership->id,
                'user_id' => $buyer->id,
                'amount_kobo' => 7_500_000_000,
                'paid_on' => now()->subMonths(12 - $n * 3)->toDateString(),
                'reference' => "REF-$n",
            ]);
        }

        app(PaymentController::class)->recalculateSchedule($ownership);

        return $ownership->fresh();
    }

    private function form(Ownership $ownership, array $overrides = []): array
    {
        return [
            'property_unit_id' => '',
            'agreed_price' => '600,000,000',
            'payment_plan' => 'instalment',
            'instalment_frequency' => 'quarterly',
            'total_instalments' => 8,
            'start_date' => $ownership->start_date->toDateString(),
            'status' => 'active',
            ...$overrides,
        ];
    }

    public function test_remove_link_form_is_not_nested_inside_the_edit_form(): void
    {
        $ownership = $this->ownershipWithPayments();

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.ownerships.edit', $ownership))
            ->assertOk()
            ->getContent();

        // Between the update form opening and its first </form>, there must be
        // no other <form> and no DELETE method field.
        $start = strpos($html, 'action="' . route('admin.ownerships.update', $ownership) . '"');
        $this->assertNotFalse($start);
        $updateForm = substr($html, $start, strpos($html, '</form>', $start) - $start);

        $this->assertStringNotContainsString('<form', $updateForm);
        $this->assertStringNotContainsString('value="DELETE"', $updateForm);
    }

    public function test_saving_the_edit_form_keeps_payments(): void
    {
        $ownership = $this->ownershipWithPayments();

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.ownerships.update', $ownership), $this->form($ownership))
            ->assertRedirect();

        $this->assertDatabaseHas('ownerships', ['id' => $ownership->id]);
        $this->assertSame(2, $ownership->payments()->count());
    }

    public function test_regenerating_the_schedule_reapplies_logged_payments(): void
    {
        $ownership = $this->ownershipWithPayments();

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.ownerships.update', $ownership), $this->form($ownership, [
                'total_instalments' => 10,
                'regenerate_schedule' => '1',
            ]));

        $instalments = $ownership->instalments()->orderBy('instalment_number')->get();
        $this->assertCount(10, $instalments);
        $this->assertSame(2, $instalments->where('status', 'paid')->count());
        $this->assertSame(60_000_000_000, (int) $instalments->sum('amount_kobo'));
        $this->assertSame(2, $ownership->payments()->count());
    }

    public function test_changing_the_price_rebalances_the_schedule(): void
    {
        $ownership = $this->ownershipWithPayments();

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.ownerships.update', $ownership), $this->form($ownership, [
                'agreed_price' => '700,000,000',
            ]));

        $this->assertSame(70_000_000_000, (int) $ownership->instalments()->sum('amount_kobo'));
        $this->assertSame(2, $ownership->instalments()->where('status', 'paid')->count());
    }

    public function test_link_with_payments_cannot_be_removed(): void
    {
        $ownership = $this->ownershipWithPayments();

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.ownerships.destroy', $ownership))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('ownerships', ['id' => $ownership->id]);
        $this->assertSame(2, $ownership->payments()->count());
    }
}
