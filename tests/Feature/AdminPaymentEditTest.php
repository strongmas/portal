<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPaymentEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Mail::fake();
        Notification::fake();
    }

    private function staff(array $permissions): User
    {
        $role = AdminRole::create(['name' => 'Role ' . uniqid(), 'permissions' => $permissions]);
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true, 'admin_role_id' => $role->id])->save();

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

    public function test_staff_can_change_date_and_attach_proof_without_notifying_anyone(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment();
        $newDate = now()->subDays(10)->toDateString();

        $this->actingAs($staff)
            ->patch(route('admin.payments.update', $payment), [
                'paid_on' => $newDate,
                'proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame($newDate, $payment->paid_on->toDateString());
        $this->assertNotNull($payment->proof_path);
        Storage::disk('private')->assertExists($payment->proof_path);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Notification::assertNothingSent();
    }

    public function test_editing_does_not_touch_confirmation_or_flag_state(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment(['confirmed_at' => now(), 'confirmed_by' => $staff->id]);

        $this->actingAs($staff)->patch(route('admin.payments.update', $payment), [
            'paid_on' => now()->subDay()->toDateString(),
        ]);

        $payment->refresh();
        $this->assertNotNull($payment->confirmed_at);
        $this->assertNull($payment->flagged_at);
    }

    public function test_split_payment_legs_are_updated_together(): void
    {
        $staff = $this->staff(['payments']);
        $first = $this->payment(['payment_group_id' => 'grp-1', 'proof_path' => 'old.pdf']);
        $second = $this->payment(['user_id' => $first->user_id, 'payment_group_id' => 'grp-1', 'proof_path' => 'old.pdf']);
        $newDate = now()->subDays(3)->toDateString();

        $this->actingAs($staff)->patch(route('admin.payments.update', $first), [
            'paid_on' => $newDate,
            'proof' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHasNoErrors();

        $first->refresh();
        $second->refresh();
        $this->assertSame($newDate, $second->paid_on->toDateString());
        $this->assertNotSame('old.pdf', $first->proof_path);
        $this->assertSame($first->proof_path, $second->proof_path);
    }

    public function test_future_date_and_bad_file_type_are_rejected(): void
    {
        $staff = $this->staff(['payments']);
        $payment = $this->payment();
        $original = $payment->paid_on->toDateString();

        $this->actingAs($staff)->patch(route('admin.payments.update', $payment), [
            'paid_on' => now()->addDay()->toDateString(),
            'proof' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors(['paid_on', 'proof']);

        $this->assertSame($original, $payment->refresh()->paid_on->toDateString());
    }

    public function test_staff_without_payments_permission_and_buyers_cannot_edit(): void
    {
        $payment = $this->payment();
        $body = ['paid_on' => now()->subDay()->toDateString()];

        $this->actingAs($this->staff(['tickets']))
            ->patch(route('admin.payments.update', $payment), $body)
            ->assertForbidden();

        $this->actingAs($payment->user)
            ->patch(route('admin.payments.update', $payment), $body)
            ->assertForbidden();

        $this->actingAs($this->staff(['tickets']))
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertDontSee('Edit payment');
    }
}
