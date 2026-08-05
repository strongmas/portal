<?php

namespace App\Console\Commands;

use App\Http\Controllers\PaymentController;
use App\Models\Ownership;
use Illuminate\Console\Command;

class ReconcileSchedules extends Command
{
    protected $signature = 'schedules:reconcile {--fix : Apply the recalculation instead of only reporting}';

    protected $description = 'Check every ownership: does its instalment schedule still add up to the agreed price, and does its status match its balance?';

    public function handle(): int
    {
        $problems = 0;

        foreach (Ownership::with(['instalments', 'payments', 'property'])->get() as $ownership) {
            $paid        = (int) $ownership->payments->sum('amount_kobo');
            $outstanding = max(0, (int) $ownership->agreed_price_kobo - $paid);
            $issues      = [];

            if ($ownership->instalments->isEmpty()) {
                // Nothing to reconcile — an outright purchase or a plan never generated.
                if ($ownership->payments->isNotEmpty()) {
                    $issues[] = 'has payments but no instalment schedule';
                }
            } else {
                $scheduleSum = (int) $ownership->instalments->sum('amount_kobo');
                $paidSlots   = (int) $ownership->instalments->where('status', 'paid')->sum('amount_kobo');
                $unpaidSlots = $scheduleSum - $paidSlots;

                if ($scheduleSum !== (int) $ownership->agreed_price_kobo) {
                    $issues[] = 'schedule sums to ' . naira($scheduleSum) . ' but the agreed price is ' . naira($ownership->agreed_price_kobo);
                }
                if ($paidSlots !== $paid) {
                    $issues[] = 'paid slots total ' . naira($paidSlots) . ' but ' . naira($paid) . ' has been paid';
                }
                if ($unpaidSlots !== $outstanding) {
                    $issues[] = 'unpaid slots total ' . naira($unpaidSlots) . ' but ' . naira($outstanding) . ' is outstanding';
                }
                if ($outstanding > 0 && is_null($ownership->next_due_instalment)) {
                    $issues[] = 'owes ' . naira($outstanding) . ' but has no next due instalment';
                }
            }

            if ($outstanding <= 0 && $ownership->status !== 'completed') {
                $issues[] = 'fully paid but status is "' . $ownership->status . '"';
            }
            if ($outstanding > 0 && $ownership->status === 'completed') {
                $issues[] = 'marked completed but still owes ' . naira($outstanding);
            }

            if (!$issues) {
                continue;
            }

            $problems++;
            $this->warn("Ownership #{$ownership->id} — {$ownership->display_name}");
            foreach ($issues as $issue) {
                $this->line("    · {$issue}");
            }

            if ($this->option('fix')) {
                app(PaymentController::class)->recalculateSchedule($ownership);
                $this->info('    → recalculated');
            }
        }

        $this->newLine();

        if ($problems === 0) {
            $this->info('All ownerships reconcile.');

            return self::SUCCESS;
        }

        $this->line("{$problems} ownership(s) with issues.");

        if (!$this->option('fix')) {
            $this->line('Re-run with --fix to recalculate them.');
        }

        return self::FAILURE;
    }
}
