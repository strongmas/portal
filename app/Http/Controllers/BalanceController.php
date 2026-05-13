<?php

namespace App\Http\Controllers;

use App\Models\Ownership;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $ownedOwnerships  = $user->ownerships()->with(['property', 'unit', 'payments'])->get();
        $memberOwnerships = $user->memberOwnerships()->with(['property', 'unit', 'payments'])->get();
        $ownerships       = $ownedOwnerships->merge($memberOwnerships)->unique('id');
        $memberRoles      = $memberOwnerships->mapWithKeys(fn($o) => [$o->id => $o->pivot->role]);

        if ($ownerships->count() === 1) {
            return redirect()->route('properties.show', $ownerships->first());
        }

        return view('balance.index', compact('ownerships', 'memberRoles', 'ownedOwnerships'));
    }

    public function show(Request $request, Ownership $ownership)
    {
        $this->authorize('view', $ownership);

        $user         = $request->user();
        $memberRecord = $ownership->members()->where('user_id', $user->id)->first();
        $userRole     = ($ownership->user_id === $user->id)
            ? 'primary_owner'
            : ($memberRecord?->role ?? 'viewer');

        $ownership->load(['property.images', 'property.units.images', 'unit.images', 'payments', 'instalments.paidPayment']);
        $allOwnerships = $user->ownerships()->with(['property', 'unit'])->get()
            ->merge($user->memberOwnerships()->with(['property', 'unit'])->get())
            ->unique('id');

        return view('balance.show', compact('ownership', 'allOwnerships', 'userRole'));
    }
}
