<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $payments = $request->user()->payments()
            ->with(['ownership.property', 'ownership.unit'])
            ->latest('paid_on')
            ->get();

        // null-safe: a payment whose ownership was deleted won't crash the page
        $grouped = $payments->groupBy(fn($p) => $p->ownership?->display_name ?? 'Other');

        return view('payments.history', compact('payments', 'grouped'));
    }

    public function receipt(Payment $payment)
    {
        abort_unless(auth()->user()->is_admin || (int) $payment->user_id === (int) auth()->id(), 403);

        $pdf = Pdf::loadView('pdf.receipt', compact('payment'));
        return $pdf->stream("receipt-{$payment->receipt_number}.pdf");
    }

    public function proof(Payment $payment)
    {
        abort_unless(auth()->user()->is_admin || (int) $payment->user_id === (int) auth()->id(), 403);
        abort_if(is_null($payment->proof_path), 404);

        // Report why a proof cannot be served rather than showing a bare 500 —
        // a missing object and an unreachable storage backend look identical to
        // the buyer otherwise.
        try {
            if (!Storage::disk('private')->exists($payment->proof_path)) {
                abort(404, 'This proof of payment is no longer available. Please contact support.');
            }

            return Storage::disk('private')->response($payment->proof_path);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            abort(503, 'Proof of payment storage is temporarily unavailable. Please try again shortly.');
        }
    }
}
