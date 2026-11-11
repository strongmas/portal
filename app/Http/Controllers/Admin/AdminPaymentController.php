<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminPaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['user', 'ownership.property'])
            ->latest('paid_on')
            ->paginate(30);

        $totalKobo = Payment::sum('amount_kobo');

        return view('admin.payments.index', compact('payments', 'totalKobo'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'ownership.property', 'ownership.instalments', 'confirmedBy']);
        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Records that staff checked the payment against the bank. Balances are not
     * touched: they already count a payment from the moment it is submitted.
     * The legs of a split payment are one transfer, so they confirm together.
     */
    public function confirm(Request $request, Payment $payment)
    {
        $legs = $payment->payment_group_id
            ? Payment::where('payment_group_id', $payment->payment_group_id)
            : Payment::whereKey($payment->id);

        if ((clone $legs)->whereNotNull('flagged_at')->exists()) {
            return back()->with('error', 'Remove the flag before confirming this transaction.');
        }

        $legs->whereNull('confirmed_at')->update([
            'confirmed_at' => now(),
            'confirmed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payment confirmed.');
    }

    /**
     * Silent correction by staff: change the date paid and/or attach a proof.
     * Deliberately sends no mail and records nothing the buyer can see. The
     * legs of a split payment are one transfer, so they share a date and a
     * proof and are updated together.
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'proof'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $changes = [];

        if (!$payment->paid_on->isSameDay($validated['paid_on'])) {
            $changes['paid_on'] = $validated['paid_on'];
        }

        if ($request->hasFile('proof')) {
            $path = null;

            try {
                $path = $request->file('proof')->store('payment-proofs/' . $payment->user_id, 'private');

                if ($path && !Storage::disk('private')->exists($path)) {
                    $path = null;
                }
            } catch (\Throwable $e) {
                report($e);
                $path = null;
            }

            if (!$path) {
                return back()->withErrors([
                    'proof' => 'The proof could not be saved, so nothing was changed. Please try again.',
                ]);
            }

            // The previous file is kept on disk on purpose: replacing a proof
            // should never be the step that destroys the only copy of one.
            $changes['proof_path'] = $path;
        }

        if (!$changes) {
            return back()->with('success', 'Nothing to update.');
        }

        $legs = $payment->payment_group_id
            ? Payment::where('payment_group_id', $payment->payment_group_id)->get()
            : collect([$payment]);

        DB::transaction(function () use ($legs, $changes) {
            foreach ($legs as $leg) {
                $leg->update($changes);
            }
        });

        // Instalments are matched to payments in date order, so a new date can
        // move a payment onto a different instalment.
        if (isset($changes['paid_on'])) {
            $schedule = app(PaymentController::class);
            foreach ($legs as $leg) {
                if ($leg->ownership) {
                    $schedule->recalculateSchedule($leg->ownership);
                }
            }
        }

        return back()->with('success', 'Payment updated.');
    }

    public function flag(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'flag_reason' => ['required', 'string', 'max:500'],
        ]);

        // Clearing the confirmation is what lets the buyer delete and resubmit.
        $payment->update([
            'flagged_at'   => now(),
            'flag_reason'  => $validated['flag_reason'],
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);

        return back()->with('success', 'Transaction flagged. The buyer can now review and delete it.');
    }

    public function unflag(Payment $payment)
    {
        $payment->update([
            'flagged_at'  => null,
            'flag_reason' => null,
        ]);

        return back()->with('success', 'Flag removed from transaction.');
    }
}
