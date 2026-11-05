<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

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
