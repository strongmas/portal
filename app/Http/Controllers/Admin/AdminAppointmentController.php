<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\SalesRep;
use Illuminate\Http\Request;

class AdminAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['user', 'property', 'salesRep'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->paginate(25);
        return view('admin.appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['user', 'property', 'salesRep']);
        $reps = SalesRep::where('is_active', true)->orderBy('name')->get();
        return view('admin.appointments.show', compact('appointment', 'reps'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status'        => ['required', 'in:pending,confirmed,inspected,cancelled,rescheduled'],
            'sales_rep_id'  => ['nullable', 'exists:sales_reps,id'],
            'outcome_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $appointment->update($validated);

        return back()->with('success', 'Appointment updated.');
    }
}
