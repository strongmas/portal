<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $buyer)
    {
        abort_if($buyer->is_admin, 404);

        if ($buyer->is_archived) {
            return back()->with('error', 'Restore this buyer before logging in as them.');
        }

        $admin = $request->user();

        Auth::login($buyer);
        $request->session()->put(self::SESSION_KEY, $admin->id);

        Log::info('Super admin started impersonating buyer', ['admin_id' => $admin->id, 'buyer_id' => $buyer->id]);

        return redirect()->route('home');
    }

    public function stop(Request $request)
    {
        $admin = User::find($request->session()->get(self::SESSION_KEY));
        $buyer = $request->user();

        $request->session()->forget(self::SESSION_KEY);

        // The admin may have lost their role since starting: never restore it blindly.
        if (!$admin?->isSuperAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Auth::login($admin);

        Log::info('Super admin stopped impersonating buyer', ['admin_id' => $admin->id, 'buyer_id' => $buyer->id]);

        return redirect()->route('admin.buyers.show', $buyer);
    }
}
