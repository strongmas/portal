<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index()
    {
        $currentId = session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentId) {
                $session->is_current   = $session->id === $currentId;
                $session->last_seen    = \Carbon\Carbon::createFromTimestamp($session->last_activity);
                $session->agent        = $session->user_agent ?? 'Unknown';
                return $session;
            });

        return view('sessions.index', compact('sessions'));
    }

    public function destroyOthers(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        return back()->with('success', 'All other sessions have been terminated.');
    }

    public function destroy(string $sessionId)
    {
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        return back()->with('success', 'Session terminated.');
    }
}
