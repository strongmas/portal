<?php

namespace App\Http\Controllers;

use App\Mail\StaffAlertMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        if ($request->input('mode') === 'split') {
            return $this->storeSplit($request);
        }

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

        // Prevent duplicate submissions (same reference for same property)
        if (!empty($validated['reference']) && Payment::where('ownership_id', $ownership->id)->where('reference', $validated['reference'])->exists()) {
            return back()->withInput()->withErrors(['reference' => 'A payment with this reference has already been logged for this property.']);
        }

        // kobo() strips non-numeric characters, so "abc" or "0" both resolve to 0.
        // A zero payment would otherwise be logged and consume an instalment slot.
        $amountKobo = kobo($validated['amount']);
        if ($amountKobo <= 0) {
            return back()->withInput()->withErrors(['amount' => 'Enter the amount you paid — it must be greater than ₦0.']);
        }

        if ($amountKobo > $ownership->outstanding_kobo) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Amount exceeds the outstanding balance of ' . naira($ownership->outstanding_kobo) . '. You cannot overpay on a property.']);
        }

        // Store the proof first. If the write fails (unwritable storage dir on
        // the server, disk full, truncated upload) we must not create the
        // payment — a payment row with no proof looks successful but is useless.
        $path = $this->storeProof($request, ['ownership_id' => $ownership->id]);

        if (!$path) {
            return back()->withInput()->withErrors([
                'proof' => 'We could not save your proof of payment, so the payment was not logged. Please try again — if it keeps failing, contact support and we will log it for you.',
            ]);
        }

        Payment::create([
            'ownership_id' => $ownership->id,
            'user_id'      => $request->user()->id,
            'amount_kobo'  => $amountKobo,
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

    /**
     * One bank transfer covering several properties. Writes one Payment row per
     * property, all sharing a payment_group_id and the same proof file, so each
     * ownership keeps its own ledger while the legs stay linked to one transfer.
     */
    protected function storeSplit(Request $request)
    {
        $validated = $request->validate([
            'amounts'   => ['required', 'array'],
            'paid_on'   => ['required', 'date'],
            'reference' => ['required', 'string', 'max:255'],
            'bank'      => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'proof'     => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $user = $request->user();

        // Only the buyer's own, still-payable ownerships are eligible.
        $eligible = $user->ownerships()
            ->where('status', '!=', 'completed')
            ->with(['property', 'unit'])
            ->get()
            ->keyBy('id');

        // Keep only the rows the buyer actually filled in.
        $allocations = [];
        foreach ($validated['amounts'] as $ownershipId => $rawAmount) {
            $amountKobo = kobo((string) $rawAmount);
            if ($amountKobo <= 0) {
                continue;
            }

            $ownership = $eligible->get((int) $ownershipId);
            if (!$ownership) {
                return back()->withInput()->withErrors([
                    'amounts' => 'One of the selected properties is not yours or is already fully paid. Please reload the page and try again.',
                ]);
            }

            if ($amountKobo > $ownership->outstanding_kobo) {
                return back()->withInput()->withErrors([
                    'amounts' => "The amount for {$ownership->display_name} exceeds its outstanding balance of "
                        . naira($ownership->outstanding_kobo) . '. You cannot overpay on a property.',
                ]);
            }

            if (Payment::where('ownership_id', $ownership->id)->where('reference', $validated['reference'])->exists()) {
                return back()->withInput()->withErrors([
                    'reference' => "A payment with this reference has already been logged for {$ownership->display_name}.",
                ]);
            }

            $allocations[] = ['ownership' => $ownership, 'amount_kobo' => $amountKobo];
        }

        if (count($allocations) < 2) {
            return back()->withInput()->withErrors([
                'amounts' => 'Enter an amount for at least two properties, or switch back to a single-property payment.',
            ]);
        }

        $path = $this->storeProof($request, [
            'ownership_ids' => collect($allocations)->pluck('ownership.id')->all(),
        ]);

        if (!$path) {
            return back()->withInput()->withErrors([
                'proof' => 'We could not save your proof of payment, so nothing was logged. Please try again — if it keeps failing, contact support and we will log it for you.',
            ]);
        }

        $groupId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($allocations, $groupId, $validated, $user, $path) {
                foreach ($allocations as $allocation) {
                    Payment::create([
                        'ownership_id'     => $allocation['ownership']->id,
                        'user_id'          => $user->id,
                        'payment_group_id' => $groupId,
                        'amount_kobo'      => $allocation['amount_kobo'],
                        'paid_on'          => $validated['paid_on'],
                        'reference'        => $validated['reference'],
                        'bank'             => $validated['bank'] ?? null,
                        'notes'            => $validated['notes'] ?? null,
                        'proof_path'       => $path,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            Storage::disk('private')->delete($path);

            return back()->withInput()->withErrors([
                'amounts' => 'We could not log the split payment. Nothing was saved — please try again.',
            ]);
        }

        foreach ($allocations as $allocation) {
            $this->recalculateSchedule($allocation['ownership']);
        }

        $total = collect($allocations)->sum('amount_kobo');
        $lines = collect($allocations)
            ->map(fn($a) => "  · {$a['ownership']->display_name}: " . naira($a['amount_kobo']))
            ->implode("\n");

        User::withAdminPermission('payments')->each(function ($staff) use ($user, $validated, $total, $lines, $allocations) {
            Mail::to($staff->email)->send(new StaffAlertMail(
                alertSubject: "Split payment submitted by {$user->name}",
                heading: 'Split payment submitted for review',
                body: "Buyer: {$user->name}\nOne transfer of " . naira($total) . ' split across '
                    . count($allocations) . " properties:\n{$lines}\nReference: {$validated['reference']}",
                actionUrl: route('admin.payments.index'),
                actionLabel: 'View Payments',
            ));
        });

        return redirect()->route('properties.index')->with(
            'success',
            naira($total) . ' logged across ' . count($allocations)
                . ' properties. Each balance and instalment schedule has been updated.'
        );
    }

    /**
     * Persist the uploaded proof, returning its path or false if the write failed.
     * $context is logged verbatim to make a failure traceable.
     */
    protected function storeProof(Request $request, array $context = []): string|false
    {
        $failure = null;

        try {
            $path = $request->file('proof')->store(
                'payment-proofs/' . $request->user()->id,
                'private'
            );

            // The existence check can throw too — on a remote disk (S3/R2) a bad
            // credential fails the HEAD request, not just the PUT.
            if ($path && !Storage::disk('private')->exists($path)) {
                $path = false;
            }
        } catch (\Throwable $e) {
            $path    = false;
            $failure = $e->getMessage();
            report($e);
        }

        if (!$path) {
            Log::error('Payment proof upload failed', array_merge([
                'user_id'     => $request->user()->id,
                'original'    => $request->file('proof')?->getClientOriginalName(),
                'error'       => $request->file('proof')?->getError(),
                'disk'        => config('filesystems.disks.private.driver'),
                'reason'      => $failure,
            ], $context));

            return false;
        }

        return $path;
    }

    public function destroy(Payment $payment)
    {
        abort_unless($payment->user_id === auth()->id(), 403);

        $ownership = $payment->ownership;

        // A split payment's legs share one proof file — only delete it once the
        // last leg is gone, or the surviving legs lose their proof.
        $proofStillInUse = $payment->proof_path && Payment::where('proof_path', $payment->proof_path)
            ->where('id', '!=', $payment->id)
            ->exists();

        if ($payment->proof_path && !$proofStillInUse) {
            // A storage outage must not block the buyer from correcting their
            // ledger — leave the orphaned file behind and carry on.
            try {
                Storage::disk('private')->delete($payment->proof_path);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $payment->delete();

        $this->recalculateSchedule($ownership);

        return back()->with('success', 'Payment deleted. Your ledger has been updated.');
    }

    /**
     * Recalculate the instalment schedule after a payment is added or removed.
     * Rule: each payment (in chronological order) covers exactly one instalment.
     * The instalment's recorded amount is updated to match the payment.
     * The remaining outstanding balance is spread evenly across future slots.
     *
     * Invariant: the schedule always sums to the agreed price — every naira paid
     * appears against a paid instalment, and every naira still owed appears
     * against an unpaid one. Two edge cases are handled to keep that true:
     *
     *  - More payments than instalment slots: the surplus payments are folded
     *    into the last paid slot rather than dropped.
     *  - Money still owed but no slot left to owe it in: the final slot is kept
     *    payable so the outstanding balance stays visible and `next_due_instalment`
     *    keeps returning a row. (Previously the debt vanished from the schedule
     *    and the buyer saw a fully-paid plan while still owing.)
     */
    public function recalculateSchedule(Ownership $ownership): void
    {
        $instalments = $ownership->instalments()->orderBy('instalment_number')->get();
        if ($instalments->isEmpty()) return;

        $payments    = $ownership->payments()->orderBy('paid_on')->orderBy('id')->get();
        $totalPaid   = (int) $payments->sum('amount_kobo');
        $outstanding = max(0, (int) $ownership->agreed_price_kobo - $totalPaid);

        $paidCount = min($payments->count(), $instalments->count());

        // Never consume every slot while money is still owed — the balance needs
        // somewhere to live. (With a single-slot plan there is nothing to hold
        // back, so the one slot shows what is still due.)
        if ($outstanding > 0 && $paidCount === $instalments->count()) {
            $paidCount--;
        }

        $remainingCount = $instalments->count() - $paidCount;

        $newAmountEach = ($remainingCount > 0 && $outstanding > 0)
            ? (int) floor($outstanding / $remainingCount)
            : 0;

        $today        = now()->startOfDay();
        $firstUnpaid  = true;
        $remainingIdx = 0;

        foreach ($instalments as $idx => $inst) {
            if ($idx < $paidCount) {
                // Each payment maps 1-to-1 to one instalment in chronological order.
                // The last paid slot absorbs any payments beyond the slot count so
                // that the paid slots always add up to exactly what was paid.
                $payment  = $payments->get($idx);
                $isLastPaid = ($idx === $paidCount - 1);

                $amountPaid = $isLastPaid
                    ? (int) $payments->slice($idx)->sum('amount_kobo')
                    : (int) $payment->amount_kobo;

                $inst->update([
                    'amount_kobo'     => $amountPaid,
                    'status'          => 'paid',
                    'paid_payment_id' => $payment->id,
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
