<?php

namespace App\Http\Controllers;

use App\Mail\StaffAlertMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $ownerships = $request->user()->ownerships()
            ->where('status', '!=', 'completed')
            ->with(['property', 'unit'])
            ->get();

        if ($ownerships->isEmpty()) {
            return redirect()->route('properties.index')
                ->with('success', 'All your properties are fully paid. No payment needed!');
        }

        $selectedOwnership = $request->query('ownership')
            ? $ownerships->firstWhere('id', $request->query('ownership'))
            : $ownerships->first();

        return view('payments.create', compact('ownerships', 'selectedOwnership'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ownership_id' => ['required', 'exists:ownerships,id'],
            'amount'        => ['required', 'string'],
            'paid_on'       => ['required', 'date'],
            'reference'     => ['required', 'string', 'max:255'],
            'bank'          => ['nullable', 'string', 'max:100'],
            'notes'         => ['nullable', 'string', 'max:1000'],
            'proof'         => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $ownership = Ownership::where('id', $validated['ownership_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($ownership->status === 'completed') {
            return back()->withErrors(['ownership_id' => 'This property is fully paid — no further payments are needed.']);
        }

        $amountKobo = kobo($validated['amount']);
        if ($amountKobo > $ownership->outstanding_kobo) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Amount exceeds the outstanding balance of ' . naira($ownership->outstanding_kobo) . '. You cannot overpay on a property.']);
        }

        $path = $request->file('proof')->store(
            'payment-proofs/' . $request->user()->id,
            'private'
        );

        Payment::create([
            'ownership_id' => $ownership->id,
            'user_id'      => $request->user()->id,
            'amount_kobo'  => kobo($validated['amount']),
            'paid_on'      => $validated['paid_on'],
            'reference'    => $validated['reference'],
            'bank'         => $validated['bank'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'proof_path'   => $path,
        ]);

        $this->recalculateSchedule($ownership);

        // Notify staff with 'payments' permission
        $buyer = $request->user();
        $ownership->load('property');
        User::withAdminPermission('payments')->each(function ($staff) use ($buyer, $ownership, $validated) {
            Mail::to($staff->email)->send(new StaffAlertMail(
                alertSubject: "New payment submitted by {$buyer->name}",
                heading: "Payment submitted for review",
                body: "Buyer: {$buyer->name}\nProperty: {$ownership->property->development_name}\nAmount: " . naira(kobo($validated['amount'])) . "\nReference: {$validated['reference']}",
                actionUrl: route('admin.payments.index'),
                actionLabel: 'View Payments',
            ));
        });

        return redirect()->route('properties.show', $ownership)
            ->with('success', 'Payment logged. Your balance and instalment schedule have been updated.');
    }

    public function destroy(Payment $payment)
    {
        abort_unless($payment->user_id === auth()->id(), 403);

        $ownership = $payment->ownership;

        if ($payment->proof_path) {
            Storage::disk('private')->delete($payment->proof_path);
        }

        $payment->delete();

        $this->recalculateSchedule($ownership);

        return back()->with('success', 'Payment deleted. Your ledger has been updated.');
    }

    /**
     * Recalculate the instalment schedule from scratch based on current total paid.
     * Called after both storing and deleting a payment.
     */
    public function recalculateSchedule(Ownership $ownership): void
    {
        $instalments = $ownership->instalments()->orderBy('instalment_number')->get();
        if ($instalments->isEmpty()) return;

        $totalPaid   = (int) $ownership->payments()->sum('amount_kobo');
        $outstanding = max(0, (int) $ownership->agreed_price_kobo - $totalPaid);

        $budget         = $totalPaid;
        $paidUntilIndex = -1;

        foreach ($instalments as $idx => $inst) {
            if ($budget >= $inst->amount_kobo) {
                $budget -= $inst->amount_kobo;
                $paidUntilIndex = $idx;
            } else {
                break;
            }
        }

        // For each paid instalment, find the payment whose cumulative total first
        // crossed the instalment's running threshold — that payment "completed" it.
        $paymentAtIndex = [];
        if ($paidUntilIndex >= 0) {
            $payments   = $ownership->payments()->orderBy('paid_on')->orderBy('id')->get();
            $running    = 0;
            $thresholds = [];
            foreach ($instalments->take($paidUntilIndex + 1) as $idx => $inst) {
                $running          += $inst->amount_kobo;
                $thresholds[$idx]  = $running;
            }
            $cumulative = 0;
            foreach ($payments as $payment) {
                $cumulative += $payment->amount_kobo;
                foreach ($thresholds as $idx => $threshold) {
                    if (!isset($paymentAtIndex[$idx]) && $cumulative >= $threshold) {
                        $paymentAtIndex[$idx] = $payment->id;
                    }
                }
            }
        }

        $remainingCount = $instalments->count() - ($paidUntilIndex + 1);

        $newAmountEach = ($remainingCount > 0 && $outstanding > 0)
            ? (int) floor($outstanding / $remainingCount)
            : 0;

        $today       = now()->startOfDay();
        $firstUnpaid = true;
        $remainingIdx = 0;

        foreach ($instalments as $idx => $inst) {
            if ($idx <= $paidUntilIndex) {
                $inst->update([
                    'status'          => 'paid',
                    'paid_payment_id' => $paymentAtIndex[$idx] ?? null,
                ]);
            } else {
                $remainingIdx++;
                $isLast = ($remainingIdx === $remainingCount);

                $amount = $isLast
                    ? max(0, $outstanding - ($newAmountEach * ($remainingCount - 1)))
                    : $newAmountEach;

                if ($firstUnpaid) {
                    $status      = 'due';
                    $firstUnpaid = false;
                } elseif ($inst->due_date->lt($today)) {
                    $status = 'overdue';
                } else {
                    $status = 'upcoming';
                }

                $inst->update([
                    'amount_kobo'     => $amount,
                    'status'          => $status,
                    'paid_payment_id' => null,
                ]);
            }
        }

        if ($outstanding <= 0 && $ownership->status !== 'completed') {
            $ownership->update(['status' => 'completed']);
        } elseif ($outstanding > 0 && $ownership->status === 'completed') {
            $ownership->update(['status' => 'active']);
        }
    }
}
