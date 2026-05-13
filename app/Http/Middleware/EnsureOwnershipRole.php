<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOwnershipRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user      = $request->user();
        $ownership = $request->route('ownership');

        if (!$ownership) {
            abort(403);
        }

        $member = $ownership->members()->where('user_id', $user->id)->first();

        if (!$member || !in_array($member->role, $roles)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
