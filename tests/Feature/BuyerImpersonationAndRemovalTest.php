<?php

namespace Tests\Feature;

use App\Mail\ProjectUpdateMail;
use App\Models\AdminRole;
use App\Models\LoginActivity;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerImpersonationAndRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function staff(array $permissions = ['buyers', 'payments']): User
    {
        $role = AdminRole::create(['name' => 'Role ' . uniqid(), 'permissions' => $permissions]);

        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true, 'admin_role_id' => $role->id])->save();

        return $user;
    }

    private function ownership(User $buyer): Ownership
    {
        $property = Property::create([
            'code' => 'STR-' . uniqid(),
            'development_name' => 'Test Estate',
            'address' => 'Lagos',
        ]);

        return Ownership::create([
            'user_id' => $buyer->id,
            'property_id' => $property->id,
            'agreed_price_kobo' => 100_000_000,
            'payment_plan' => 'outright',
            'total_instalments' => 1,
            'start_date' => now(),
            'status' => 'active',
        ]);
    }

    private function payment(User $buyer, ?Ownership $ownership = null): Payment
    {
        return Payment::create([
            'ownership_id' => ($ownership ?? $this->ownership($buyer))->id,
            'user_id' => $buyer->id,
            'amount_kobo' => 5_000_000,
            'paid_on' => now()->toDateString(),
            'reference' => 'REF-' . uniqid(),
        ]);
    }

    // ── Log in as buyer ────────────────────────────────────────────────────

    public function test_super_admin_can_log_in_as_a_buyer_and_return(): void
    {
        $admin = $this->superAdmin();
        $buyer = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.buyers.impersonate', $buyer))
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($buyer);

        $this->get(route('home'))->assertOk()
            ->assertSee('You are logged in as')
            ->assertSee(route('impersonation.stop'));

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.buyers.show', $buyer))
            ->assertSessionMissing('impersonator_id');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_only_a_super_admin_can_log_in_as_a_buyer(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($this->staff())->post(route('admin.buyers.impersonate', $buyer))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('admin.buyers.impersonate', $buyer))->assertForbidden();
    }

    public function test_cannot_log_in_as_an_admin_or_an_archived_buyer(): void
    {
        $admin = $this->superAdmin();
        $archived = User::factory()->create();
        $archived->forceFill(['archived_at' => now()])->save();

        $this->actingAs($admin)->post(route('admin.buyers.impersonate', $this->staff()))->assertNotFound();

        $this->actingAs($admin)->post(route('admin.buyers.impersonate', $archived))->assertSessionHas('error');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_normal_buyer_cannot_become_an_admin_through_the_stop_route(): void
    {
        $this->superAdmin();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('impersonation.stop'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_stopping_does_not_restore_an_admin_who_lost_super_admin_access(): void
    {
        $admin = $this->superAdmin();
        $buyer = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.buyers.impersonate', $buyer));
        $admin->forceFill(['is_admin' => false])->save();

        $this->post(route('impersonation.stop'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_payment_logged_while_impersonating_records_the_admin(): void
    {
        Mail::fake();
        Storage::fake('private');

        $admin = $this->superAdmin();
        $buyer = User::factory()->create();
        $ownership = $this->ownership($buyer);

        $this->actingAs($admin)->post(route('admin.buyers.impersonate', $buyer));

        $this->post(route('payments.store'), [
            'ownership_id' => $ownership->id,
            'amount' => '50000',
            'paid_on' => now()->toDateString(),
            'reference' => 'REF-ADMIN',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHasNoErrors();

        $payment = Payment::where('reference', 'REF-ADMIN')->firstOrFail();
        $this->assertSame($buyer->id, (int) $payment->user_id);
        $this->assertSame($admin->id, (int) $payment->logged_by);

        $this->post(route('impersonation.stop'));
        $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee($admin->name);
    }

    public function test_payment_logged_by_the_buyer_has_no_admin_recorded(): void
    {
        Mail::fake();
        Storage::fake('private');

        $buyer = User::factory()->create();
        $ownership = $this->ownership($buyer);

        $this->actingAs($buyer)->post(route('payments.store'), [
            'ownership_id' => $ownership->id,
            'amount' => '50000',
            'paid_on' => now()->toDateString(),
            'reference' => 'REF-BUYER',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertNull(Payment::where('reference', 'REF-BUYER')->firstOrFail()->logged_by);
    }

    public function test_password_and_profile_cannot_be_changed_while_impersonating(): void
    {
        $buyer = User::factory()->create(['name' => 'Original Name']);
        $originalHash = $buyer->password;

        $this->actingAs($this->superAdmin())->post(route('admin.buyers.impersonate', $buyer));

        $this->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHas('error');
        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHas('error');
        $this->patch(route('profile.update'), ['name' => 'Changed', 'email' => 'changed@example.com'])
            ->assertSessionHas('error');

        $buyer->refresh();
        $this->assertSame($originalHash, $buyer->password);
        $this->assertSame('Original Name', $buyer->name);
    }

    public function test_impersonating_does_not_add_to_the_buyers_login_history(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($this->superAdmin())->post(route('admin.buyers.impersonate', $buyer));
        $this->post(route('impersonation.stop'));

        $this->assertSame(0, LoginActivity::where('user_id', $buyer->id)->count());
    }

    // ── Archive ────────────────────────────────────────────────────────────

    public function test_archiving_hides_the_buyer_and_keeps_their_records(): void
    {
        $admin = $this->superAdmin();
        $buyer = User::factory()->create(['name' => 'Archived Person']);
        $payment = $this->payment($buyer);

        $this->actingAs($admin)->post(route('admin.buyers.archive', $buyer))->assertSessionHas('success');

        $this->assertTrue($buyer->fresh()->is_archived);
        $this->assertNotNull($payment->fresh());

        // The first page after archiving names the buyer in its success message.
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('has been archived');

        $this->get(route('admin.buyers.index'))->assertOk()->assertDontSee('Archived Person');
        $this->get(route('admin.kyc.index'))->assertOk()->assertDontSee('Archived Person');
        $this->get(route('admin.buyers.index', ['archived' => 1]))->assertOk()->assertSee('Archived Person');
        $this->get(route('admin.buyers.show', $buyer))->assertOk()->assertSee(route('admin.buyers.restore', $buyer));
        $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('Archived Person');
    }

    public function test_archived_buyer_cannot_log_in_and_restoring_lets_them_back(): void
    {
        $admin = $this->superAdmin();
        $buyer = User::factory()->create(['password' => 'password']);
        $buyer->forceFill(['archived_at' => now()])->save();

        $this->post('/login', ['email' => $buyer->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->post(route('admin.buyers.restore', $buyer));
        $this->post(route('logout'));

        $this->post('/login', ['email' => $buyer->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($buyer);
    }

    public function test_archiving_ends_a_session_that_is_already_open(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('home'))->assertOk();
        $buyer->forceFill(['archived_at' => now()])->save();

        $this->actingAs($buyer->fresh())->get(route('home'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_archived_buyer_gets_no_project_update_emails(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->superAdmin();
        $active = User::factory()->create();
        $archived = User::factory()->create();
        $archived->forceFill(['archived_at' => now()])->save();

        $ownership = $this->ownership($active);
        Ownership::create([
            'user_id' => $archived->id,
            'property_id' => $ownership->property_id,
            'agreed_price_kobo' => 100_000_000,
            'payment_plan' => 'outright',
            'total_instalments' => 1,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.project-updates.store', $ownership->property_id), ['title' => 'Site update'])
            ->assertRedirect();

        Mail::assertSent(ProjectUpdateMail::class, fn ($mail) => $mail->hasTo($active->email));
        Mail::assertNotSent(ProjectUpdateMail::class, fn ($mail) => $mail->hasTo($archived->email));
    }

    public function test_only_a_super_admin_can_archive_restore_or_delete(): void
    {
        $staff = $this->staff();
        $buyer = User::factory()->create();

        $this->actingAs($staff)->post(route('admin.buyers.archive', $buyer))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.buyers.restore', $buyer))->assertForbidden();
        $this->actingAs($staff)->delete(route('admin.buyers.destroy', $buyer), ['confirm_name' => $buyer->name])->assertForbidden();

        $this->assertFalse($buyer->fresh()->is_archived);

        $this->actingAs($staff)->get(route('admin.buyers.show', $buyer))
            ->assertOk()
            ->assertDontSee(route('admin.buyers.archive', $buyer))
            ->assertDontSee(route('admin.buyers.impersonate', $buyer))
            ->assertDontSee('Delete permanently');
    }

    // ── Permanent delete ───────────────────────────────────────────────────

    public function test_buyer_with_no_payments_can_be_permanently_deleted_with_their_files(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $buyer = User::factory()->create(['name' => 'Test Duplicate', 'avatar_path' => 'avatars/a.jpg']);
        $ownership = $this->ownership($buyer);
        $ticket = SupportTicket::create(['user_id' => $buyer->id, 'case_no' => 'C-1', 'category' => 'Other', 'body' => 'Help', 'status' => 'open']);
        SupportMessage::create(['user_id' => $buyer->id, 'sender' => 'buyer', 'body' => 'Hi', 'attachment_path' => 'support-attachments/x.pdf', 'attachment_name' => 'x.pdf']);
        Storage::disk('private')->put('support-attachments/x.pdf', 'file');
        Storage::disk('public')->put('avatars/a.jpg', 'file');

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.buyers.destroy', $buyer), ['confirm_name' => 'Test Duplicate'])
            ->assertRedirect(route('admin.buyers.index'))
            ->assertSessionHas('success');

        $this->assertNull($buyer->fresh());
        $this->assertNull($ownership->fresh());
        $this->assertNull($ticket->fresh());
        Storage::disk('private')->assertMissing('support-attachments/x.pdf');
        Storage::disk('public')->assertMissing('avatars/a.jpg');
    }

    public function test_delete_needs_the_exact_name_typed(): void
    {
        $buyer = User::factory()->create(['name' => 'Test Duplicate']);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.buyers.destroy', $buyer), ['confirm_name' => 'test'])
            ->assertSessionHas('error');

        $this->assertNotNull($buyer->fresh());
    }

    public function test_buyer_with_payments_cannot_be_permanently_deleted(): void
    {
        $admin = $this->superAdmin();
        $buyer = User::factory()->create(['name' => 'Paying Buyer']);
        $payment = $this->payment($buyer);

        $this->actingAs($admin)->get(route('admin.buyers.show', $buyer))
            ->assertOk()
            ->assertDontSee('Delete permanently');

        $this->actingAs($admin)
            ->delete(route('admin.buyers.destroy', $buyer), ['confirm_name' => 'Paying Buyer'])
            ->assertSessionHas('error');

        $this->assertNotNull($buyer->fresh());
        $this->assertNotNull($payment->fresh());
    }

    public function test_payment_by_a_co_owner_also_blocks_permanent_delete(): void
    {
        $buyer = User::factory()->create(['name' => 'Primary Owner']);
        $ownership = $this->ownership($buyer);
        $payment = $this->payment(User::factory()->create(), $ownership);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.buyers.destroy', $buyer), ['confirm_name' => 'Primary Owner'])
            ->assertSessionHas('error');

        $this->assertNotNull($buyer->fresh());
        $this->assertNotNull($payment->fresh());
    }

    public function test_admin_accounts_cannot_be_deleted_through_the_buyer_route(): void
    {
        $staff = $this->staff();

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.buyers.destroy', $staff), ['confirm_name' => $staff->name])
            ->assertNotFound();

        $this->assertNotNull($staff->fresh());
    }
}
