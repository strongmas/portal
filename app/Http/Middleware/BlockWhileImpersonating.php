<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\AdminImpersonationController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keeps a buyer's login details and personal profile theirs to change. */
class BlockWhileImpersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has(AdminImpersonationController::SESSION_KEY)) {
            return back()->with('error', "You can't change a buyer's profile or password while logged in as them.");
        }

        return $next($request);
    }
}
