<?php

namespace App\Http\Controllers;

use App\Events\AppointmentBooked;
use App\Models\Appointment;
use App\Models\Property;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = auth()->user()
            ->appointments()
            ->with(['property', 'salesRep'])
            ->latest()
            ->paginate(10);

        return view('appointments.index', compact('appointments'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasOwnerAccess(), 403, 'Only property owners and co-owners can book inspections.');

        $properties = Property::orderBy('development_name')->get();
        $selected = $request->filled('property_id')
            ? Property::findOrFail($request->property_id)
            : null;

        return view('appointments.create', compact('properties', 'selected'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasOwnerAccess(), 403, 'Only property owners and co-owners can book inspections.');

        $validated = $request->validate([
            'property_id'    => ['required', 'exists:properties,id'],
            'preferred_date' => ['required', 'date', 'after:today'],
            'preferred_time' => ['required', 'date_format:H:i'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = Appointment::create([
            'user_id'        => auth()->id(),
            'property_id'    => $validated['property_id'],
            'sales_rep_id'   => auth()->user()->sales_rep_id,
            'preferred_date' => $validated['preferred_date'],
            'preferred_time' => $validated['preferred_time'],
            'notes'          => $validated['notes'] ?? null,
            'status'         => 'pending',
        ]);

        AppointmentBooked::dispatch($appointment);

        return redirect()->route('appointments.index')
            ->with('success', 'Inspection request submitted. We will confirm shortly.');
    }

    public function cancel(Appointment $appointment)
    {
        $this->authorize('cancel', $appointment);

        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Appointment cancelled.');
    }
}
