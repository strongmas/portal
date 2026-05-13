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
        $payment->load(['user', 'ownership.property', 'ownership.instalments']);
        return view('admin.payments.show', compact('payment'));
    }

    public function flag(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'flag_reason' => ['required', 'string', 'max:500'],
        ]);

        $payment->update([
            'flagged_at'  => now(),
            'flag_reason' => $validated['flag_reason'],
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
