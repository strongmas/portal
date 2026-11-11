<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Mail\PropertyLinkedMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminOwnershipController extends Controller
{
    public function create(Request $request)
    {
        $buyers     = User::activeBuyers()->orderBy('name')->get();
        $properties = Property::with('units')->orderBy('development_name')->get();
        $units      = PropertyUnit::with('property')->get();

        $selectedBuyer    = $request->query('buyer_id');
        $selectedProperty = $request->query('property_id');

        return view('admin.ownerships.create', compact(
            'buyers', 'properties', 'units', 'selectedBuyer', 'selectedProperty'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'                  => ['required', 'exists:users,id'],
            'property_id'              => ['required', 'exists:properties,id'],
            'property_unit_id'         => ['nullable', 'exists:property_units,id'],
            'agreed_price'             => ['required', 'string'],
            'payment_plan'             => ['required', 'in:outright,instalment'],
            'instalment_frequency'     => ['nullable', 'in:monthly,quarterly,bi-annually'],
            'total_instalments'        => ['required', 'integer', 'min:1', 'max:360'],
            'start_date'               => ['required', 'date'],
            'expected_completion_date' => ['nullable', 'date', 'after:start_date'],
            'status'                   => ['required', 'in:active,completed,cancelled'],
        ]);

        $ownership = Ownership::create([
            'user_id'                  => $validated['user_id'],
            'property_id'              => $validated['property_id'],
            'property_unit_id'         => $validated['property_unit_id'] ?: null,
            'agreed_price_kobo'        => kobo($validated['agreed_price']),
            'payment_plan'             => $validated['payment_plan'],
            'instalment_frequency'     => $validated['instalment_frequency'] ?? 'monthly',
            'total_instalments'        => $validated['total_instalments'],
            'start_date'               => $validated['start_date'],
            'expected_completion_date' => $validated['expected_completion_date'] ?? null,
            'status'                   => $validated['status'],
        ]);

        if ($request->boolean('generate_schedule')) {
            $this->generateInstalments($ownership);
        }

        // Send property linked email — catch SMTP errors so they don't break the flow
        try {
            $buyer = User::find($validated['user_id']);
            $ownership->load(['property', 'unit']);
            Mail::to($buyer->email)->send(new PropertyLinkedMail($buyer, $ownership));
        } catch (\Throwable $e) {
            Log::error('PropertyLinkedMail failed for ownership #' . $ownership->id . ': ' . $e->getMessage());
        }

        return redirect()->route('admin.buyers.show', $validated['user_id'])
            ->with('success', 'Property linked successfully.');
    }

    public function edit(Ownership $ownership)
    {
        $ownership->load(['user', 'property', 'unit']);
        $properties = Property::with('units')->orderBy('development_name')->get();
        $units      = PropertyUnit::with('property')->get();
        return view('admin.ownerships.edit', compact('ownership', 'properties', 'units'));
    }

    public function update(Request $request, Ownership $ownership)
    {
        $validated = $request->validate([
            'property_unit_id'         => ['nullable', 'exists:property_units,id'],
            'agreed_price'             => ['required', 'string'],
            'payment_plan'             => ['required', 'in:outright,instalment'],
            'instalment_frequency'     => ['nullable', 'in:monthly,quarterly,bi-annually'],
            'total_instalments'        => ['required', 'integer', 'min:1', 'max:360'],
            'start_date'               => ['required', 'date'],
            'expected_completion_date' => ['nullable', 'date'],
            'status'                   => ['required', 'in:active,completed,cancelled'],
        ]);

        $ownership->update([
            'property_unit_id'         => $validated['property_unit_id'] ?: null,
            'agreed_price_kobo'        => kobo($validated['agreed_price']),
            'payment_plan'             => $validated['payment_plan'],
            'instalment_frequency'     => $validated['instalment_frequency'] ?? $ownership->instalment_frequency ?? 'monthly',
            'total_instalments'        => $validated['total_instalments'],
            'start_date'               => $validated['start_date'],
            'expected_completion_date' => $validated['expected_completion_date'] ?? null,
            'status'                   => $validated['status'],
        ]);

        $priceChanged = $ownership->wasChanged('agreed_price_kobo');

        if ($request->boolean('regenerate_schedule')) {
            $ownership->instalments()->delete();
            $this->generateInstalments($ownership);
        }

        // A fresh schedule starts with every slot unpaid, and a new price changes
        // what is still owed — either way, put the logged payments back against
        // the schedule so the buyer's history and balance survive the edit.
        if ($request->boolean('regenerate_schedule') || $priceChanged) {
            app(PaymentController::class)->recalculateSchedule($ownership->fresh());

            // Recalculating can flip active/completed; the status the admin
            // just chose wins.
            $ownership->update(['status' => $validated['status']]);
        }

        return redirect()->route('admin.buyers.show', $ownership->user_id)
            ->with('success', 'Ownership updated.');
    }

    public function destroy(Ownership $ownership)
    {
        $buyerId = $ownership->user_id;

        // Deleting an ownership cascades to its payments in the database, so a
        // link with money logged against it must never be removed from here.
        if ($ownership->payments()->exists()) {
            return back()->with('error', 'This property has payments logged against it, so the link cannot be removed. Edit the ownership instead, or delete the payments first.');
        }

        $ownership->instalments()->delete();
        $ownership->delete();

        return redirect()->route('admin.buyers.show', $buyerId)
            ->with('success', 'Property link removed.');
    }

    private function generateInstalments(Ownership $ownership): void
    {
        $total = $ownership->total_instalments;
        if ($total <= 0) return;

        $base      = (int) floor($ownership->agreed_price_kobo / $total);
        $remainder = $ownership->agreed_price_kobo - ($base * $total);
        $start     = Carbon::parse($ownership->start_date);
        $today     = now();
        $interval  = match ($ownership->instalment_frequency ?? 'monthly') {
            'quarterly'   => 3,
            'bi-annually' => 6,
            default       => 1,
        };

        for ($i = 1; $i <= $total; $i++) {
            $due    = $start->copy()->addMonths(($i - 1) * $interval);
            $amount = $i === $total ? $base + $remainder : $base;

            if ($due->lt($today->startOfMonth())) {
                $status = 'overdue';
            } elseif ($due->format('Y-m') === $today->format('Y-m')) {
                $status = 'due';
            } else {
                $status = 'upcoming';
            }

            Instalment::create([
                'ownership_id'      => $ownership->id,
                'instalment_number' => $i,
                'due_date'          => $due->toDateString(),
                'amount_kobo'       => $amount,
                'status'            => $status,
            ]);
        }
    }
}
