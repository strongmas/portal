<?php

namespace App\Http\Controllers;

use App\Events\GroupInvitationSent;
use App\Models\Invitation;
use App\Models\Ownership;
use App\Models\OwnershipMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function index(Ownership $ownership)
    {
        $this->authorize('view', $ownership);

        $ownership->load(['members.user', 'members.inviter']);
        $pendingInvitations = Invitation::where('ownership_id', $ownership->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get();

        return view('group.index', compact('ownership', 'pendingInvitations'));
    }

    public function invite(Request $request, Ownership $ownership)
    {
        $this->authorize('manage', $ownership);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'in:owner,viewer,financial'],
        ]);

        // Check if already a member
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            $alreadyMember = $ownership->members()->where('user_id', $existingUser->id)->exists();
            if ($alreadyMember || $ownership->user_id === $existingUser->id) {
                return back()->with('error', 'This person is already a member of this property.');
            }
        }

        // Cancel any existing pending invite for this email+ownership
        Invitation::where('ownership_id', $ownership->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->delete();

        $invitation = Invitation::create([
            'token'        => Str::random(64),
            'ownership_id' => $ownership->id,
            'inviter_id'   => auth()->id(),
            'email'        => $validated['email'],
            'role'         => $validated['role'],
            'expires_at'   => now()->addDays(7),
        ]);

        GroupInvitationSent::dispatch($invitation);

        return back()->with('success', "Invitation sent to {$validated['email']}.");
    }

    public function removeMember(Ownership $ownership, User $member)
    {
        $this->authorize('manage', $ownership);

        if ($member->id === $ownership->user_id) {
            return back()->with('error', 'Cannot remove the primary owner.');
        }

        $deleted = $ownership->members()->where('user_id', $member->id)->delete();

        abort_unless($deleted > 0, 404);

        return back()->with('success', 'Member removed.');
    }

    public function cancelInvitation(Ownership $ownership, Invitation $invitation)
    {
        $this->authorize('manage', $ownership);

        if ($invitation->ownership_id !== $ownership->id) {
            abort(403);
        }

        if ($invitation->isAccepted()) {
            return back()->with('error', 'This invitation has already been accepted.');
        }

        $email = $invitation->email;
        $invitation->delete();

        return back()->with('success', "Invitation to {$email} has been cancelled.");
    }

    public function updateRole(Request $request, Ownership $ownership, OwnershipMember $ownershipMember)
    {
        $this->authorize('manage', $ownership);

        // Prevent IDOR: ensure the member actually belongs to this ownership
        abort_unless((int) $ownershipMember->ownership_id === (int) $ownership->id, 403);

        $validated = $request->validate(['role' => ['required', 'in:owner,viewer,financial']]);
        $ownershipMember->update($validated);

        return back()->with('success', 'Role updated.');
    }
}
