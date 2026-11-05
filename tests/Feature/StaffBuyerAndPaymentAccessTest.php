<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffBuyerAndPaymentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $permissions): User
    {
        $role = AdminRole::create(['name' => 'Role ' . uniqid(), 'permissions' => $permissions]);

        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true, 'admin_role_id' => $role->id])->save();

        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function payment(array $attributes = []): Payment
    {
        $buyer = $attributes['user_id'] ?? User::factory()->create()->id;

        $property = Property::create([
            'code' => 'STR-' . uniqid(),
            'development_name' => 'Test Estate',
            'address' => 'Lagos',
        ]);

        $ownership = Ownership::create([
            'user_id' => $buyer,
            'property_id' => $property->id,
            'agreed_price_kobo' => 100_000_000,
            'payment_plan' => 'outright',
            'total_instalments' => 1,
            'start_date' => now(),
            'status' => 'active',
        ]);

        return Payment::create([
            'ownership_id' => $ownership->id,
            'user_id' => $buyer,
            'amount_kobo' => 5_000_000,
            'paid_on' => now()->toDateString(),
            'reference' => 'REF-' . uniqid(),
            ...$attributes,
        ]);
    }

    public function test_staff_with_buyers_permission_can_view_buyers_read_only(): void
    {
        $staff = $this->staff(['buyers']);
        $buyer = $this->payment()->user;

        $this->actingAs($staff)->get(route('admin.buyers.index'))
            ->assertOk()
            ->assertSee($buyer->name)
            ->assertDontSee(route('admin.buyers.create'))
            ->assertDontSee(route('admin.buyers.edit', $buyer));

        $this->actingAs($staff)->get(route('admin.buyers.show', $buyer))
            ->assertOk()
            ->assertSee($buyer->email)
            ->assertDontSee(route('admin.buyers.reset-password', $buyer))
            ->assertDontSee(route('admin.buyers.assign-rep', $buyer));
    }

    public function test_staff_without_buyers_permission_cannot_view_buyers(): void
    {
        $staff = $this->staff(['tickets']);
        $buyer = User::factory()->create();

        $this->actingAs($staff)->get(route('admin.buyers.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.buyers.show', $buyer))->assertForbidden();
    }

    public function test_buyer_editing_stays_super_admin_only(): void
    {
        $staff = $this->staff(['buyers', 'payments']);
        $buyer = User::factory()->create();

        $this->actingAs($staff)->get(route('admin.buyers.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.buyers.edit', $buyer))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.buyers.reset-password', $buyer))->assertForbidden();
    }

    public function test_super_admin_keeps_full_buyer_access_including_the_create_page(): void
    {
        $admin = $this->superAdmin();
        $buyer = $this->payment()->user;

        $this->actingAs($admin)->get(route('admin.buyers.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.buyers.show', $buyer))
            ->assertOk()
            ->assertSee(route('admin.buyers.edit', $buyer));
    }

    public function test_staff_with_payments_permission_can_confirm_a_payment(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment();

        $this->actingAs($staff)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee(route('admin.payments.confirm', $payment));

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $payment))->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->is_confirmed);
        $this->assertSame($staff->id, (int) $payment->confirmed_by);

        $this->actingAs($staff)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee($staff->name);
    }

    public function test_confirming_does_not_change_the_balance(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment();
        $before = $payment->ownership->fresh()->paid_kobo;

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $payment));

        $this->assertSame($before, $payment->ownership->fresh()->paid_kobo);
    }

    public function test_staff_without_payments_permission_cannot_confirm(): void
    {
        $staff = $this->staff(['buyers']);
        $payment = $this->payment();

        $this->actingAs($staff)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertDontSee(route('admin.payments.confirm', $payment));

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $payment))->assertForbidden();

        $this->assertFalse($payment->fresh()->is_confirmed);
    }

    public function test_confirming_one_leg_confirms_the_whole_split_transfer(): void
    {
        $staff = $this->staff(['payments']);
        $first = $this->payment(['payment_group_id' => 'group-1']);
        $second = $this->payment(['payment_group_id' => 'group-1', 'user_id' => $first->user_id]);
        $unrelated = $this->payment(['user_id' => $first->user_id]);

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $first));

        $this->assertTrue($first->fresh()->is_confirmed);
        $this->assertTrue($second->fresh()->is_confirmed);
        $this->assertFalse($unrelated->fresh()->is_confirmed);
    }

    public function test_a_flagged_payment_cannot_be_confirmed(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment(['flagged_at' => now(), 'flag_reason' => 'Wrong amount']);

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $payment))
            ->assertSessionHas('error');

        $this->assertFalse($payment->fresh()->is_confirmed);
    }

    public function test_a_split_transfer_with_a_flagged_leg_cannot_be_confirmed(): void
    {
        $staff = $this->staff(['payments']);
        $clean = $this->payment(['payment_group_id' => 'group-2']);
        $this->payment([
            'payment_group_id' => 'group-2',
            'user_id' => $clean->user_id,
            'flagged_at' => now(),
            'flag_reason' => 'Wrong amount',
        ]);

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $clean))
            ->assertSessionHas('error');

        $this->assertFalse($clean->fresh()->is_confirmed);
    }

    public function test_buyer_cannot_delete_a_confirmed_payment_until_it_is_flagged(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment();
        $buyer = $payment->user;

        $this->actingAs($staff)->patch(route('admin.payments.confirm', $payment));

        $this->actingAs($buyer)->delete(route('payments.destroy', $payment))->assertForbidden();
        $this->assertNotNull($payment->fresh());

        $this->actingAs($staff)->patch(route('admin.payments.flag', $payment), ['flag_reason' => 'Wrong amount']);
        $this->assertFalse($payment->fresh()->is_confirmed);

        $this->actingAs($buyer)->delete(route('payments.destroy', $payment))->assertRedirect();
        $this->assertNull($payment->fresh());
    }

    public function test_migration_grants_client_experience_role_and_leaves_others_alone(): void
    {
        $clientExperience = AdminRole::create(['name' => 'Client Experience', 'permissions' => ['tickets']]);
        $other = AdminRole::create(['name' => 'Sales', 'permissions' => ['interests']]);

        $migration = require database_path('migrations/2026_11_05_000002_grant_client_experience_buyer_and_payment_access.php');
        $migration->up();

        $this->assertEqualsCanonicalizing(['tickets', 'buyers', 'payments'], $clientExperience->fresh()->permissions);
        $this->assertSame(['interests'], $other->fresh()->permissions);
    }
}
