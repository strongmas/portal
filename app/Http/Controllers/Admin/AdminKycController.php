<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminKycController extends Controller
{
    public function index()
    {
        $pending  = User::where('is_admin', false)
            ->whereNull('kyc_verified_at')
            ->withCount('ownerships')
            ->with(['ownerships.property', 'ownerships.unit'])
            ->latest()
            ->get();

        $verified = User::where('is_admin', false)
            ->whereNotNull('kyc_verified_at')
            ->withCount('ownerships')
            ->orderByDesc('kyc_verified_at')
            ->take(10)
            ->get();

        return view('admin.kyc.index', compact('pending', 'verified'));
    }

    public function approve(User $user)
    {
        $user->update(['kyc_verified_at' => now()]);

        return back()->with('success', "{$user->name}'s KYC has been approved.");
    }

    public function reject(Request $request, User $user)
    {
        $user->update(['kyc_verified_at' => null]);

        return back()->with('success', "{$user->name}'s KYC status has been reset.");
    }
}
