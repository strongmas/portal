<?php

namespace App\Console\Commands;

use App\Http\Controllers\PaymentController;
use App\Mail\WelcomeBuyerMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\SalesRep;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Rebuild buyers, ownerships and payments from CSV files after the production
 * database was lost. The CSVs are filled in by hand from staff alert emails,
 * "property linked" emails and bank statements (recovery:extract-emails drafts
 * them from exported .eml files).
 *
 * Everything is validated before anything is written, and the whole import
 * runs in one transaction. Re-running is safe: buyers are matched by email,
 * ownerships by buyer + property + unit, payments by ownership + reference.
 */
class RestoreRecords extends Command
{
    protected $signature = 'recovery:import
        {dir : Folder containing buyers.csv, ownerships.csv and payments.csv}
        {--execute : Actually write. Without this the command only reports what it would do}
        {--send-invites : After importing, email each newly created buyer a temporary password}';

    protected $description = 'Restore buyers, ownerships, instalment schedules and payments from recovery CSV files';

    private array $errors = [];

    public function handle(): int
    {
        $dir = rtrim($this->argument('dir'), '/');

        $buyers     = $this->readCsv("{$dir}/buyers.csv", ['name', 'email']);
        $ownerships = $this->readCsv("{$dir}/ownerships.csv", ['buyer_email', 'property', 'agreed_price', 'payment_plan', 'start_date']);
        $payments   = $this->readCsv("{$dir}/payments.csv", ['buyer_email', 'property', 'amount', 'paid_on', 'reference'], required: false);

        if ($this->errors) {
            return $this->failWithErrors();
        }

        $buyers     = $this->validateBuyers($buyers);
        $ownerships = $this->validateOwnerships($ownerships, $buyers);
        $payments   = $this->validatePayments($payments, $ownerships);

        if ($this->errors) {
            return $this->failWithErrors();
        }

        $execute = $this->option('execute');
        $this->info($execute ? 'Restoring records' : 'DRY RUN — nothing will be written');
        $this->newLine();

        // A dry run goes through exactly the same writes and is then rolled back,
        // so it catches anything the database itself would reject.
        DB::beginTransaction();
        try {
            $created = $this->restore($buyers, $ownerships, $payments);
            $execute ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->newLine();

        if (!$execute) {
            $this->line('Re-run with --execute to write these records.');

            return self::SUCCESS;
        }

        $this->call('schedules:reconcile');

        if ($this->option('send-invites')) {
            $this->sendInvites($created);
        } elseif ($created->isNotEmpty()) {
            $this->line("{$created->count()} new buyer(s) have no usable password yet. Re-run with --send-invites, or reset each one's password from the admin panel.");
        }

        return self::SUCCESS;
    }

    /**
     * Write everything. Runs inside the caller's transaction, so a dry run
     * exercises exactly the same code and is then rolled back.
     *
     * @return Collection<int, array{user: User, property: string}> buyers created by this run
     */
    private function restore(Collection $buyers, Collection $ownerships, Collection $payments): Collection
    {
        $users   = [];
        $created = collect();

        foreach ($buyers as $row) {
            $user = User::where('email', $row['email'])->first();

            if ($user) {
                $this->line("  buyer      exists   {$row['email']}");
            } else {
                $user = User::create([
                    'name'         => $row['name'],
                    'email'        => $row['email'],
                    'phone'        => $row['phone'],
                    'sales_rep_id' => $row['sales_rep_id'],
                    // Unusable until an invite or admin reset issues a real one.
                    'password'     => Hash::make(Str::random(40)),
                ]);
                $created->put($user->email, ['user' => $user, 'property' => null]);
                $this->line("  buyer      create   {$row['email']}");
            }

            $users[$row['email']] = $user;
        }

        $restored = [];

        foreach ($ownerships as $key => $row) {
            $user = $users[$row['buyer_email']];

            $ownership = Ownership::where('user_id', $user->id)
                ->where('property_id', $row['property']->id)
                ->where('property_unit_id', $row['unit']?->id)
                ->first();

            $label = $row['property']->development_name . ($row['unit'] ? ' · ' . $row['unit']->label : '');

            if ($ownership) {
                $this->line("  ownership  exists   {$row['buyer_email']} → {$label}");
                if ((int) $ownership->agreed_price_kobo !== $row['agreed_price_kobo']) {
                    $this->warn('             portal has ' . naira($ownership->agreed_price_kobo) . ' but the CSV says ' . naira($row['agreed_price_kobo']) . ' — left unchanged');
                }
            } else {
                $ownership = Ownership::create([
                    'user_id'                  => $user->id,
                    'property_id'              => $row['property']->id,
                    'property_unit_id'         => $row['unit']?->id,
                    'agreed_price_kobo'        => $row['agreed_price_kobo'],
                    'payment_plan'             => $row['payment_plan'],
                    'instalment_frequency'     => $row['instalment_frequency'],
                    'total_instalments'        => $row['total_instalments'],
                    'start_date'               => $row['start_date'],
                    'expected_completion_date' => $row['expected_completion_date'],
                    'status'                   => 'active',
                ]);

                if ($row['payment_plan'] === 'instalment') {
                    $this->createInstalmentSlots($ownership);
                }

                $this->line("  ownership  create   {$row['buyer_email']} → {$label} (" . naira($row['agreed_price_kobo']) . ')');
            }

            if ($created->has($row['buyer_email']) && !$created[$row['buyer_email']]['property']) {
                $created[$row['buyer_email']] = ['user' => $user, 'property' => $label];
            }

            $restored[$key] = $ownership;
        }

        $groupIds = [];

        foreach ($payments as $row) {
            $ownership = $restored[$row['ownership_key']];

            if (Payment::where('ownership_id', $ownership->id)->where('reference', $row['reference'])->exists()) {
                $this->line("  payment    exists   {$row['reference']} ({$row['buyer_email']})");
                continue;
            }

            $groupId = null;
            if ($row['group'] !== null) {
                $groupId = $groupIds[$row['group']] ??= (string) Str::uuid();
            }

            $payment = new Payment([
                'ownership_id'     => $ownership->id,
                'user_id'          => $ownership->user_id,
                'payment_group_id' => $groupId,
                'amount_kobo'      => $row['amount_kobo'],
                'paid_on'          => $row['paid_on'],
                'reference'        => $row['reference'],
                'bank'             => $row['bank'],
                'notes'            => $row['notes'] ?? 'Restored after data loss — proof of payment to be re-submitted.',
            ]);
            // Date the record by the transfer, so receipts and monthly totals
            // land in the month the money actually arrived.
            $payment->created_at = $row['paid_on'];
            $payment->save();

            $this->line("  payment    create   {$row['reference']} " . naira($row['amount_kobo']) . " on {$row['paid_on']} ({$row['buyer_email']})");
        }

        $scheduler = app(PaymentController::class);
        foreach ($restored as $ownership) {
            $ownership = $ownership->fresh();
            $scheduler->recalculateSchedule($ownership);

            // recalculateSchedule only settles plans that have a schedule; an
            // outright purchase restored as fully paid must be closed here.
            if ($ownership->instalments()->doesntExist() && $ownership->outstanding_kobo === 0) {
                $ownership->update(['status' => 'completed']);
            }
        }

        return $created;
    }

    /**
     * Lay out the instalment slots with their due dates. Amounts and statuses
     * are left for recalculateSchedule, which spreads the balance across them
     * once the restored payments are in.
     */
    private function createInstalmentSlots(Ownership $ownership): void
    {
        $interval = match ($ownership->instalment_frequency) {
            'quarterly'   => 3,
            'bi-annually' => 6,
            default       => 1,
        };

        for ($i = 1; $i <= $ownership->total_instalments; $i++) {
            Instalment::create([
                'ownership_id'      => $ownership->id,
                'instalment_number' => $i,
                'due_date'          => $ownership->start_date->copy()->addMonths(($i - 1) * $interval)->toDateString(),
                'amount_kobo'       => 0,
                'status'            => 'upcoming',
            ]);
        }
    }

    private function sendInvites(Collection $created): void
    {
        foreach ($created as ['user' => $user, 'property' => $property]) {
            $tempPassword = Str::random(12);
            $user->update(['password' => Hash::make($tempPassword)]);

            try {
                Mail::to($user->email)->send(new WelcomeBuyerMail($user, $tempPassword, $property));
                $this->info("  invite sent   {$user->email}");
            } catch (\Throwable $e) {
                Log::error('WelcomeBuyerMail failed for user #' . $user->id . ': ' . $e->getMessage());
                $this->warn("  invite FAILED {$user->email} — reset their password from the admin panel");
            }
        }
    }

    // ── Validation ─────────────────────────────────────────────────────────

    private function validateBuyers(Collection $rows): Collection
    {
        $seenEmails = [];
        $seenPhones = [];
        $reps       = SalesRep::all();

        return $rows->map(function ($row, $line) use (&$seenEmails, &$seenPhones, $reps) {
            $where = "buyers.csv line {$line}";
            $email = Str::lower($row['email']);
            $phone = $row['phone'] ?? null;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "{$where}: \"{$row['email']}\" is not a valid email";
            } elseif (isset($seenEmails[$email])) {
                $this->errors[] = "{$where}: {$email} is already listed on line {$seenEmails[$email]}";
            }
            $seenEmails[$email] ??= $line;

            if ($phone) {
                if (isset($seenPhones[$phone])) {
                    $this->errors[] = "{$where}: phone {$phone} is already used on line {$seenPhones[$phone]} (phone numbers must be unique)";
                }
                $seenPhones[$phone] ??= $line;

                $holder = User::where('phone', $phone)->first();
                if ($holder && Str::lower($holder->email) !== $email) {
                    $this->errors[] = "{$where}: phone {$phone} already belongs to {$holder->email}";
                }
            }

            $existing = User::where('email', $email)->first();
            if ($existing?->is_admin) {
                $this->errors[] = "{$where}: {$email} is a staff account, not a buyer";
            }

            $repId = null;
            if ($rep = $row['sales_rep'] ?? null) {
                $match = $reps->first(fn ($r) => Str::lower($r->email) === Str::lower($rep) || Str::lower($r->name) === Str::lower($rep));
                if ($match) {
                    $repId = $match->id;
                } else {
                    $this->errors[] = "{$where}: no sales rep called \"{$rep}\" (known: " . ($reps->pluck('name')->implode(', ') ?: 'none') . ')';
                }
            }

            return [
                'name'         => $row['name'],
                'email'        => $email,
                'phone'        => $phone,
                'sales_rep_id' => $repId,
            ];
        });
    }

    private function validateOwnerships(Collection $rows, Collection $buyers): Collection
    {
        $buyerEmails = $buyers->pluck('email')->flip();
        $properties  = Property::with('units')->get();
        $seen        = [];

        return $rows->mapWithKeys(function ($row, $line) use ($buyerEmails, $properties, &$seen) {
            $where = "ownerships.csv line {$line}";
            $email = Str::lower($row['buyer_email']);

            if (!$buyerEmails->has($email)) {
                $this->errors[] = "{$where}: buyer {$email} is not in buyers.csv";
            }

            [$property, $unit] = $this->resolveProperty($row['property'], $row['unit'] ?? null, $properties, $where);

            $priceKobo = kobo($row['agreed_price']);
            if ($priceKobo <= 0) {
                $this->errors[] = "{$where}: agreed_price \"{$row['agreed_price']}\" must be greater than ₦0";
            }

            $plan = Str::lower($row['payment_plan']);
            if (!in_array($plan, ['outright', 'instalment'], true)) {
                $this->errors[] = "{$where}: payment_plan must be \"outright\" or \"instalment\", got \"{$row['payment_plan']}\"";
            }

            $frequency = Str::lower($row['instalment_frequency'] ?? '') ?: 'monthly';
            if (!in_array($frequency, ['monthly', 'quarterly', 'bi-annually'], true)) {
                $this->errors[] = "{$where}: instalment_frequency must be monthly, quarterly or bi-annually";
            }

            $count = (int) ($row['total_instalments'] ?? 0);
            if ($plan === 'instalment' && ($count < 1 || $count > 360)) {
                $this->errors[] = "{$where}: an instalment plan needs total_instalments between 1 and 360";
            }

            $start      = $this->parseDate($row['start_date'], "{$where}: start_date");
            $completion = ($row['expected_completion_date'] ?? null)
                ? $this->parseDate($row['expected_completion_date'], "{$where}: expected_completion_date")
                : null;

            $key = $email . '|' . ($property?->id) . '|' . ($unit?->id);
            if (isset($seen[$key])) {
                $this->errors[] = "{$where}: same buyer, property and unit as line {$seen[$key]}";
            }
            $seen[$key] ??= $line;

            return [$key => [
                'line'                     => $line,
                'buyer_email'              => $email,
                'property'                 => $property,
                'unit'                     => $unit,
                'agreed_price_kobo'        => $priceKobo,
                'payment_plan'             => $plan,
                'instalment_frequency'     => $frequency,
                'total_instalments'        => $plan === 'instalment' ? $count : 1,
                'start_date'               => $start,
                'expected_completion_date' => $completion,
            ]];
        });
    }

    private function validatePayments(Collection $rows, Collection $ownerships): Collection
    {
        $properties = Property::with('units')->get();
        $seen       = [];
        $totals     = [];
        $groups     = [];

        $valid = $rows->map(function ($row, $line) use ($ownerships, $properties, &$seen, &$totals, &$groups) {
            $where = "payments.csv line {$line}";
            $email = Str::lower($row['buyer_email']);

            [$property, $unit] = $this->resolveProperty($row['property'], $row['unit'] ?? null, $properties, $where);
            $key = $email . '|' . ($property?->id) . '|' . ($unit?->id);

            // Single-payment alerts only name the development. When the unit is
            // left blank and the buyer owns exactly one unit there, that's the one.
            $candidates = collect();
            if ($property && !$unit && !$ownerships->has($key)) {
                $candidates = $ownerships->filter(fn ($o) => $o['buyer_email'] === $email && $o['property']?->id === $property->id);
                if ($candidates->count() === 1) {
                    $key = $candidates->keys()->first();
                }
            }

            if ($candidates->count() > 1) {
                $this->errors[] = "{$where}: {$email} owns several units at {$property->development_name} — fill in the unit column";
            } elseif ($property && !$ownerships->has($key)) {
                $this->errors[] = "{$where}: no row in ownerships.csv for {$email} → {$row['property']}" . (($row['unit'] ?? null) ? " · {$row['unit']}" : '');
            }

            $amountKobo = kobo($row['amount']);
            if ($amountKobo <= 0) {
                $this->errors[] = "{$where}: amount \"{$row['amount']}\" must be greater than ₦0";
            }

            $reference = trim($row['reference']);
            if (isset($seen[$key][$reference])) {
                $this->errors[] = "{$where}: reference {$reference} is already used for this property on line {$seen[$key][$reference]}";
            }
            $seen[$key][$reference] ??= $line;

            $totals[$key] = ($totals[$key] ?? 0) + $amountKobo;

            $group = ($row['group'] ?? null) ?: null;
            if ($group !== null) {
                $groups[$group][] = ['line' => $line, 'email' => $email];
            }

            return [
                'ownership_key' => $key,
                'buyer_email'   => $email,
                'amount_kobo'   => $amountKobo,
                'paid_on'       => $this->parseDate($row['paid_on'], "{$where}: paid_on"),
                'reference'     => $reference,
                'bank'          => ($row['bank'] ?? null) ?: null,
                'notes'         => ($row['notes'] ?? null) ?: null,
                'group'         => $group,
            ];
        });

        foreach ($totals as $key => $paid) {
            $ownership = $ownerships->get($key);
            if ($ownership && $paid > $ownership['agreed_price_kobo']) {
                $this->errors[] = "ownerships.csv line {$ownership['line']}: payments total " . naira($paid)
                    . ' but the agreed price is ' . naira($ownership['agreed_price_kobo']);
            }
        }

        foreach ($groups as $group => $legs) {
            if (collect($legs)->pluck('email')->unique()->count() > 1) {
                $this->errors[] = "payments.csv: split group \"{$group}\" mixes different buyers (lines "
                    . collect($legs)->pluck('line')->implode(', ') . ')';
            }
        }

        return $valid;
    }

    /** @return array{0: ?Property, 1: ?PropertyUnit} */
    private function resolveProperty(string $name, ?string $unitLabel, Collection $properties, string $where): array
    {
        $needle   = Str::lower(trim($name));
        $property = $properties->first(fn ($p) => Str::lower($p->code) === $needle || Str::lower($p->development_name) === $needle);

        if (!$property) {
            $this->errors[] = "{$where}: no property \"{$name}\" (known: " . $properties->map(fn ($p) => "{$p->development_name} [{$p->code}]")->implode(', ') . ')';

            return [null, null];
        }

        if (!$unitLabel = trim((string) $unitLabel)) {
            return [$property, null];
        }

        $unit = $property->units->first(fn ($u) => Str::lower($u->label) === Str::lower($unitLabel));

        if (!$unit) {
            $this->errors[] = "{$where}: {$property->development_name} has no unit \"{$unitLabel}\" (known: " . $property->units->map->label->implode(', ') . ')';
        }

        return [$property, $unit];
    }

    private function parseDate(string $value, string $field): ?string
    {
        try {
            return Carbon::parse(trim($value))->toDateString();
        } catch (\Throwable) {
            $this->errors[] = "{$field} \"{$value}\" is not a date (use YYYY-MM-DD)";

            return null;
        }
    }

    // ── CSV ────────────────────────────────────────────────────────────────

    /**
     * Rows keyed by their line number in the file, with lower-cased headers
     * and blank cells turned into null. Blank rows are skipped.
     */
    private function readCsv(string $path, array $requiredColumns, bool $required = true): Collection
    {
        if (!is_file($path)) {
            if ($required) {
                $this->errors[] = "{$path} not found";
            }

            return collect();
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            $this->errors[] = "{$path} is empty";

            return collect();
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); // Excel's UTF-8 BOM
        $header    = array_map(fn ($h) => Str::snake(Str::lower(trim($h))), $header);

        if ($missing = array_diff($requiredColumns, $header)) {
            fclose($handle);
            $this->errors[] = basename($path) . ' is missing column(s): ' . implode(', ', $missing);

            return collect();
        }

        $rows = collect();
        $line = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            $cells = array_map(fn ($c) => ($c = trim((string) $c)) === '' ? null : $c, $cells);

            if (!array_filter($cells, fn ($c) => $c !== null)) {
                continue;
            }

            $row = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), null));

            foreach ($requiredColumns as $column) {
                if ($row[$column] === null) {
                    $this->errors[] = basename($path) . " line {$line}: {$column} is blank";
                }
            }

            $rows->put($line, $row);
        }

        fclose($handle);

        return $rows;
    }

    private function failWithErrors(): int
    {
        $this->error('Nothing was written. Fix these and run again:');
        foreach ($this->errors as $error) {
            $this->line("  · {$error}");
        }

        return self::FAILURE;
    }
}
