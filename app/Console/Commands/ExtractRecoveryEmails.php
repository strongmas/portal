<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Draft the recovery CSVs for recovery:import from emails the portal sent
 * before its database was lost. Reads .eml files (e.g. a Zoho Mail folder
 * export) and recognises three of the portal's own emails:
 *
 *  - Welcome to Strongmas Residence Portal   → buyers.csv (name + email)
 *  - A New Property Has Been Linked …        → ownerships.csv
 *  - New / Split payment submitted by …      → payments.csv
 *
 * The output is a draft. Every row carries a `check` column saying what the
 * emails could not tell us, and paid_on is the date the alert was sent, not
 * the transfer date — confirm both against the bank statement before importing.
 */
class ExtractRecoveryEmails extends Command
{
    protected $signature = 'recovery:extract-emails
        {source : Folder of .eml files (searched recursively)}
        {--out= : Where to write the draft CSVs (default: <source>/recovery-draft)}';

    protected $description = 'Draft buyers/ownerships/payments CSVs for recovery:import from exported portal emails';

    private array $buyers     = [];
    private array $ownerships = [];
    private array $payments   = [];

    public function handle(): int
    {
        $source = rtrim($this->argument('source'), '/');
        $out    = rtrim($this->option('out') ?: "{$source}/recovery-draft", '/');

        if (!is_dir($source)) {
            $this->error("{$source} is not a folder");

            return self::FAILURE;
        }

        $files  = $this->emlFiles($source);
        $counts = ['welcome' => 0, 'linked' => 0, 'payment' => 0, 'other' => 0];

        foreach ($files as $file) {
            $email = $this->parse(file_get_contents($file), $file);
            $kind  = match (true) {
                str_starts_with($email['subject'], 'Welcome to Strongmas Residence Portal')     => $this->readWelcome($email),
                str_starts_with($email['subject'], 'A New Property Has Been Linked')            => $this->readPropertyLinked($email),
                (bool) preg_match('/^(New|Split) payment submitted by /', $email['subject'])     => $this->readPayment($email),
                default                                                                         => 'other',
            };
            $counts[$kind]++;
        }

        $this->fillPaymentEmails();

        if (!is_dir($out)) {
            mkdir($out, 0755, true);
        }

        $this->writeCsv("{$out}/buyers.csv", ['name', 'email', 'phone', 'sales_rep', 'source', 'check'], $this->buyers);
        $this->writeCsv("{$out}/ownerships.csv", ['buyer_email', 'property', 'unit', 'agreed_price', 'payment_plan', 'instalment_frequency', 'total_instalments', 'start_date', 'expected_completion_date', 'source', 'check'], $this->ownerships);
        $this->writeCsv("{$out}/payments.csv", ['buyer_email', 'buyer_name', 'property', 'unit', 'amount', 'paid_on', 'reference', 'bank', 'notes', 'group', 'source', 'check'], $this->payments);

        $this->info('Read ' . count($files) . ' .eml file(s):');
        $this->line("  welcome emails          {$counts['welcome']}");
        $this->line("  property linked emails  {$counts['linked']}");
        $this->line("  payment alerts          {$counts['payment']}");
        $this->line("  not from the portal     {$counts['other']}");
        $this->newLine();
        $this->info("Drafts written to {$out}:");
        $this->line('  buyers.csv      ' . count($this->buyers) . ' row(s)');
        $this->line('  ownerships.csv  ' . count($this->ownerships) . ' row(s)');
        $this->line('  payments.csv    ' . count($this->payments) . ' row(s)');
        $this->newLine();
        $this->warn('These are drafts. Work through the `check` column, confirm every payment against the bank statement,');
        $this->warn("then run: php artisan recovery:import {$out}");

        return self::SUCCESS;
    }

    // ── The portal's emails ────────────────────────────────────────────────

    private function readWelcome(array $email): string
    {
        $address = $email['to'];
        if (!$address || !preg_match('/Welcome, (.+?)!/u', $email['text'], $m)) {
            return 'other';
        }

        // Several welcome emails for one buyer just mean their password was
        // re-sent. A row drafted from a property linked email has no name yet.
        if (!($this->buyers[$address]['name'] ?? null)) {
            $this->buyers[$address] = [
                'name'   => trim($m[1]),
                'email'  => $address,
                'source' => basename($email['file']),
                'check'  => 'add phone and sales rep if known',
            ];
        }

        return 'welcome';
    }

    private function readPropertyLinked(array $email): string
    {
        $address = $email['to'];
        $text    = $email['text'];

        if (!$address || !preg_match('/🏠\s*(.+)/u', $text, $badge) || !preg_match('/Agreed price\s+(₦[\d,]+)/u', $text, $price)) {
            return 'other';
        }

        [$property, $unit] = $this->splitDisplayName($badge[1]);

        $instalments = preg_match('/Payment plan\s+(\d+) instalments/u', $text, $m) ? (int) $m[1] : null;
        $start       = preg_match('/Start date\s+(\d{1,2} \w+ \d{4})/u', $text, $m) ? $this->isoDate($m[1]) : null;
        $completion  = preg_match('/Expected completion\s+(\d{1,2} \w+ \d{4})/u', $text, $m) ? $this->isoDate($m[1]) : null;

        $check = [];
        if ($instalments) {
            $check[] = 'confirm instalment_frequency (the email does not say; assumed monthly)';
        }
        if (!$start) {
            $check[] = 'start_date missing from the email';
        }

        // If the terms were changed and the email re-sent, the latest one wins.
        $this->ownerships["{$address}|{$badge[1]}"] = [
            'buyer_email'              => $address,
            'property'                 => $property,
            'unit'                     => $unit,
            'agreed_price'             => $this->plainAmount($price[1]),
            'payment_plan'             => $instalments ? 'instalment' : 'outright',
            'instalment_frequency'     => $instalments ? 'monthly' : null,
            'total_instalments'        => $instalments,
            'start_date'               => $start,
            'expected_completion_date' => $completion,
            'source'                   => basename($email['file']),
            'check'                    => implode('; ', $check),
        ];

        // The greeting only has a first name, but it still proves the account.
        $this->buyers[$address] ??= [
            'name'   => null,
            'email'  => $address,
            'source' => basename($email['file']),
            'check'  => 'name unknown — the property linked email only has a first name; fill in the full name',
        ];

        return 'linked';
    }

    private function readPayment(array $email): string
    {
        $text = $email['text'];

        if (!preg_match('/Buyer:\s*(.+)/u', $text, $buyer) || !preg_match('/Reference:\s*(.+)/u', $text, $reference)) {
            return 'other';
        }

        $buyerName = trim($buyer[1]);
        $reference = trim($reference[1]);
        $sentOn    = $email['date'] ? Carbon::parse($email['date'])->toDateString() : null;
        $source    = basename($email['file']);

        if (str_starts_with($email['subject'], 'Split')) {
            preg_match_all('/·\s*(.+?):\s*(₦[\d,]+)/u', $text, $legs, PREG_SET_ORDER);
            foreach ($legs as [, $displayName, $amount]) {
                $this->addPayment($buyerName, $displayName, $amount, $sentOn, $reference, $reference, $source);
            }
        } elseif (preg_match('/Property:\s*(.+)/u', $text, $property) && preg_match('/Amount:\s*(₦[\d,]+)/u', $text, $amount)) {
            $this->addPayment($buyerName, trim($property[1]), $amount[1], $sentOn, $reference, null, $source);
        } else {
            return 'other';
        }

        return 'payment';
    }

    private function addPayment(string $buyerName, string $displayName, string $amount, ?string $sentOn, string $reference, ?string $group, string $source): void
    {
        [$property, $unit] = $this->splitDisplayName($displayName);

        // Every staff member with the payments permission got their own copy.
        $this->payments["{$buyerName}|{$displayName}|{$reference}"] ??= [
            'buyer_email' => null,
            'buyer_name'  => $buyerName,
            'property'    => $property,
            'unit'        => $unit,
            'amount'      => $this->plainAmount($amount),
            'paid_on'     => $sentOn,
            'reference'   => $reference,
            'bank'        => null,
            'notes'       => null,
            'group'       => $group,
            'source'      => $source,
            'check'       => 'paid_on is the date the alert was sent — replace with the transfer date from the bank statement',
        ];
    }

    /** Payment alerts name the buyer but don't give their email; look it up from the welcome emails. */
    private function fillPaymentEmails(): void
    {
        $byName = collect($this->buyers)
            ->filter(fn ($b) => $b['name'])
            ->groupBy(fn ($b) => Str::lower($b['name']));

        foreach ($this->payments as &$payment) {
            $matches = $byName->get(Str::lower($payment['buyer_name']), collect());

            if ($matches->count() === 1) {
                $payment['buyer_email'] = $matches->first()['email'];
            } else {
                $payment['check'] = ($matches->isEmpty()
                    ? 'no welcome email for this buyer — fill in buyer_email'
                    : 'several buyers share this name — fill in buyer_email')
                    . '; ' . $payment['check'];
            }
        }
    }

    /** "Elysian Rise · 3 Bedroom Apartment" → ["Elysian Rise", "3 Bedroom Apartment"] */
    private function splitDisplayName(string $displayName): array
    {
        $parts = array_map('trim', explode('·', $displayName, 2));

        return [$parts[0], $parts[1] ?? null];
    }

    private function plainAmount(string $naira): string
    {
        return preg_replace('/[^\d]/', '', $naira);
    }

    private function isoDate(string $date): ?string
    {
        try {
            return Carbon::createFromFormat('j F Y', $date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    // ── .eml reading ───────────────────────────────────────────────────────

    private function emlFiles(string $dir): array
    {
        $files = [];
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($items as $item) {
            if ($item->isFile() && Str::lower($item->getExtension()) === 'eml') {
                $files[] = $item->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Just enough MIME to read the portal's own emails: headers, nested
     * multiparts, quoted-printable/base64 bodies. The HTML part is flattened
     * to text, one line per block element.
     */
    private function parse(string $raw, string $file): array
    {
        [$headers, $body] = $this->splitPart($raw);

        $html = $this->findBody($headers, $body, 'text/html');
        $text = $html !== null ? $this->htmlToText($html) : ($this->findBody($headers, $body, 'text/plain') ?? '');

        preg_match('/<?([^<>\s]+@[^<>\s,]+)>?/', $headers['to'] ?? '', $to);

        return [
            'file'    => $file,
            'subject' => trim(mb_decode_mimeheader($headers['subject'] ?? '')),
            'to'      => isset($to[1]) ? Str::lower($to[1]) : null,
            'date'    => $headers['date'] ?? null,
            'text'    => $text,
        ];
    }

    /** @return array{0: array<string, string>, 1: string} */
    private function splitPart(string $raw): array
    {
        $raw   = str_replace("\r\n", "\n", $raw);
        $split = strpos($raw, "\n\n");
        $head  = $split === false ? $raw : substr($raw, 0, $split);
        $body  = $split === false ? '' : substr($raw, $split + 2);

        $headers = [];
        // Unfold continuation lines, then read "Name: value".
        foreach (explode("\n", preg_replace('/\n[ \t]+/', ' ', $head)) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[Str::lower(trim($name))] ??= trim($value);
            }
        }

        return [$headers, $body];
    }

    private function findBody(array $headers, string $body, string $wanted): ?string
    {
        $type = Str::lower($headers['content-type'] ?? 'text/plain');

        if (str_starts_with($type, 'multipart/')) {
            if (!preg_match('/boundary="?([^";]+)"?/i', $headers['content-type'], $m)) {
                return null;
            }

            foreach (explode('--' . $m[1], $body) as $part) {
                if (trim($part) === '' || str_starts_with($part, '--')) {
                    continue;
                }
                [$partHeaders, $partBody] = $this->splitPart(ltrim($part, "\n"));
                if (($found = $this->findBody($partHeaders, $partBody, $wanted)) !== null) {
                    return $found;
                }
            }

            return null;
        }

        if (!str_starts_with($type, $wanted)) {
            return null;
        }

        return match (Str::lower($headers['content-transfer-encoding'] ?? '')) {
            'quoted-printable' => quoted_printable_decode($body),
            'base64'           => base64_decode($body),
            default            => $body,
        };
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('#<(style|script|head)\b.*?</\1>#is', '', $html);
        $html = preg_replace('#<br\s*/?>|</(div|p|h\d|li|tr)>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = array_map(fn ($l) => trim(preg_replace('/\s+/u', ' ', $l)), explode("\n", $text));

        return implode("\n", array_filter($lines, fn ($l) => $l !== ''));
    }

    // ── Output ─────────────────────────────────────────────────────────────

    private function writeCsv(string $path, array $columns, array $rows): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($c) => $row[$c] ?? null, $columns));
        }

        fclose($handle);
    }
}
