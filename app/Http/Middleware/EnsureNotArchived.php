<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Ends any session (or remember-me login) still open when a buyer is archived. */
class EnsureNotArchived
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_archived) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'This account is no longer active. Please contact support.');
        }

        return $next($request);
    }
}
