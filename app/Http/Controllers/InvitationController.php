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

        return view('invitations.show', compact('invitation'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired() || $invitation->isAccepted()) {
            return redirect()->route('home')->with('error', 'This invitation is no longer valid.');
        }

        // Check if user with this email exists
        $user = User::where('email', $invitation->email)->first();

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
        }

        // Add them as a group member
        OwnershipMember::firstOrCreate(
            ['ownership_id' => $invitation->ownership_id, 'user_id' => $user->id],
            ['role' => $invitation->role, 'invited_by' => $invitation->inviter_id, 'accepted_at' => now()],
        );

        $invitation->update(['accepted_at' => now()]);

        auth()->login($user);

        return redirect()->route('home')->with('success', 'Welcome! You now have access to the shared property.');
    }
}
