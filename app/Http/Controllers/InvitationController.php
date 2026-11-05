<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\OwnershipMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return view('invitations.expired');
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('home')->with('success', 'This invitation has already been accepted.');
        }

        $invitedUser = User::where('email', $invitation->email)->first();

        // Brings them back here after they log in.
        if ($invitedUser && auth()->guest()) {
            redirect()->setIntendedUrl(route('invitations.show', $token));
        }

        return view('invitations.show', compact('invitation', 'invitedUser'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired() || $invitation->isAccepted()) {
            return redirect()->route('home')->with('error', 'This invitation is no longer valid.');
        }

        // Check if user with this email exists
        $user = User::where('email', $invitation->email)->first();

        // The link alone must never open an existing account: the token proves
        // access to the invitation, not to the account. The page explains what to do.
        if ($user && auth()->id() !== $user->id) {
            return redirect()->route('invitations.show', $token);
        }

        if (!$user) {
            // New user — register them
            $request->validate([
                'name'     => ['required', 'string', 'max:200'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $invitation->email,
                'password' => Hash::make($request->password),
            ]);

            auth()->login($user);
        }

        // Add them as a group member
        OwnershipMember::firstOrCreate(
            ['ownership_id' => $invitation->ownership_id, 'user_id' => $user->id],
            ['role' => $invitation->role, 'invited_by' => $invitation->inviter_id, 'accepted_at' => now()],
        );

        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('home')->with('success', 'Welcome! You now have access to the shared property.');
    }
}
