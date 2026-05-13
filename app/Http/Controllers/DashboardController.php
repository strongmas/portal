<?php

namespace App\Http\Controllers;

use App\Models\MarketInsight;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $ownedOwnerships  = $user->ownerships()->with(['property.images', 'unit', 'payments', 'instalments'])->get();
        $memberOwnerships = $user->memberOwnerships()->with(['property.images', 'unit', 'payments', 'instalments'])->get();
        $ownerships       = $ownedOwnerships->merge($memberOwnerships)->unique('id');

        // Map of ownership_id → member role (absent key = primary owner)
        $memberRoles = $memberOwnerships->mapWithKeys(fn($o) => [$o->id => $o->pivot->role]);

        // $hasActive only reflects ownerships the user actually owns (for payment CTAs)
        $hasActive = $ownedOwnerships->contains(fn($o) => $o->outstanding_kobo > 0);

        // Auto-heal: only for owned ownerships
        foreach ($ownedOwnerships as $o) {
            if ($o->status !== 'completed' && $o->outstanding_kobo <= 0 && $o->payments->isNotEmpty()) {
                $o->update(['status' => 'completed']);
                $o->status = 'completed';
            }
        }

        // Next due instalment across owned ownerships only
        $nextDue = null;
        foreach ($ownedOwnerships as $o) {
            $next = $o->next_due_instalment;
            if ($next && (!$nextDue || $next->due_date->lt($nextDue->due_date))) {
                $nextDue = $next;
                $nextDue->ownership = $o;
            }
        }

        $recentPayments = $user->payments()
            ->with(['ownership.property', 'ownership.unit'])
            ->latest('paid_on')
            ->take(5)
            ->get();

        // Latest published market insight per property (keyed by property_id)
        $propertyIds = $ownerships->pluck('property_id')->unique()->filter();
        $latestInsights = MarketInsight::where('is_published', true)
            ->whereIn('property_id', $propertyIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('property_id')
            ->map->first();

        return view('dashboard', compact('user', 'ownerships', 'nextDue', 'recentPayments', 'memberRoles', 'hasActive', 'latestInsights'));
    }
}
