<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Ownership;
use App\Models\OwnershipMember;
use App\Models\Property;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function invitationFor(string $email): Invitation
    {
        $owner = User::factory()->create();

        $property = Property::create([
            'code' => 'STR-' . uniqid(),
            'development_name' => 'Test Estate',
            'address' => 'Lagos',
        ]);

        $ownership = Ownership::create([
            'user_id' => $owner->id,
            'property_id' => $property->id,
            'agreed_price_kobo' => 100_000_000,
            'payment_plan' => 'outright',
            'total_instalments' => 1,
            'start_date' => now(),
            'status' => 'active',
        ]);

        return Invitation::create([
            'token' => Str::random(64),
            'ownership_id' => $ownership->id,
            'inviter_id' => $owner->id,
            'email' => $email,
            'role' => 'viewer',
            'expires_at' => now()->addDays(7),
        ]);
    }

    private function assertNotAccepted(Invitation $invitation, User $user): void
    {
        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertFalse(
            OwnershipMember::where('ownership_id', $invitation->ownership_id)->where('user_id', $user->id)->exists()
        );
    }

    public function test_the_link_does_not_log_a_guest_into_an_existing_account(): void
    {
        $user = User::factory()->create();
        $invitation = $this->invitationFor($user->email);

        $this->post(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('invitations.show', $invitation->token));

        $this->assertGuest();
        $this->assertNotAccepted($invitation, $user);
    }

    public function test_the_link_does_not_log_a_guest_into_an_admin_account(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $invitation = $this->invitationFor($admin->email);

        $this->post(route('invitations.accept', $invitation->token));

        $this->assertGuest();
        $this->assertNotAccepted($invitation, $admin);
    }

    public function test_guest_with_an_account_is_asked_to_log_in_and_returned_to_the_invitation(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $invitation = $this->invitationFor($user->email);

        $this->get(route('invitations.show', $invitation->token))
            ->assertOk()
            ->assertSee('Log in to accept')
            ->assertDontSee('Accept Invitation');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('invitations.show', $invitation->token));

        $this->get(route('invitations.show', $invitation->token))
            ->assertOk()
            ->assertSee('Accept Invitation');
    }

    public function test_logged_in_invitee_can_accept(): void
    {
        $user = User::factory()->create();
        $invitation = $this->invitationFor($user->email);

        $this->actingAs($user)->post(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertTrue(
            OwnershipMember::where('ownership_id', $invitation->ownership_id)->where('user_id', $user->id)->exists()
        );
        $this->assertAuthenticatedAs($user);
    }

    public function test_someone_logged_in_as_another_user_cannot_accept_or_switch_account(): void
    {
        $invitee = User::factory()->create();
        $other = User::factory()->create();
        $invitation = $this->invitationFor($invitee->email);

        $this->actingAs($other)->get(route('invitations.show', $invitation->token))
            ->assertOk()
            ->assertSee('different account')
            ->assertDontSee('Accept Invitation');

        $this->actingAs($other)->post(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('invitations.show', $invitation->token));

        $this->assertAuthenticatedAs($other);
        $this->assertNotAccepted($invitation, $invitee);
        $this->assertNotAccepted($invitation, $other);
    }

    public function test_new_person_can_still_create_an_account_and_accept(): void
    {
        $invitation = $this->invitationFor('new.person@example.com');

        $this->get(route('invitations.show', $invitation->token))
            ->assertOk()
            ->assertSee('Create Account');

        $this->post(route('invitations.accept', $invitation->token), [
            'name' => 'New Person',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'new.person@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_in_app_notification_links_to_a_page_that_opens(): void
    {
        $user = User::factory()->create();
        $invitation = $this->invitationFor($user->email);

        $url = (new GroupInvitationNotification($invitation))->toArray($user)['url'];

        $this->actingAs($user)->get($url)->assertOk()->assertSee('Accept Invitation');
    }
}
