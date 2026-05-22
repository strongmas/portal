<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Instalment;
use App\Models\InterestRegistration;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Console\Command;

class PurgeBuyers extends Command
{
    protected $signature = 'buyers:purge {--force : Skip confirmation prompt}';
    protected $description = 'Delete all buyer accounts and their related data (ownerships, payments, tickets, etc.). Properties are untouched.';

    public function handle(): int
    {
        $buyerIds = User::where('is_admin', false)->pluck('id');
        $ownershipIds = Ownership::whereIn('user_id', $buyerIds)->pluck('id');

        $this->info('Records that will be permanently deleted:');
        $this->table(['Table', 'Count'], [
            ['users (buyers)',        $buyerIds->count()],
            ['ownerships',            $ownershipIds->count()],
            ['payments',              Payment::whereIn('ownership_id', $ownershipIds)->count()],
            ['instalments',           Instalment::whereIn('ownership_id', $ownershipIds)->count()],
            ['support_tickets',       SupportTicket::whereIn('user_id', $buyerIds)->count()],
            ['appointments',          Appointment::whereIn('user_id', $buyerIds)->count()],
            ['interest_registrations', InterestRegistration::whereIn('user_id', $buyerIds)->count()],
            ['(+ cascade: ownership_members, invitations, login_activities, comments...)', ''],
        ]);

        $this->info('Properties, units, images, and documents will NOT be touched.');

        if ($buyerIds->isEmpty()) {
            $this->warn('No buyers found. Nothing to delete.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Are you sure you want to permanently delete all of the above?', false)) {
            $this->info('Aborted.');
            return 0;
        }

        $this->info('Deleting...');

        // Deleting users cascades ownerships → payments, instalments, ownership_members,
        // invitations, support_tickets, appointments, interest_registrations,
        // login_activities, update_comments, google_calendar_tokens, reminder_preferences.
        $deleted = User::where('is_admin', false)->delete();

        $this->info("Done. {$deleted} buyer account(s) and all related data deleted.");

        return 0;
    }
}
