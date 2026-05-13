<?php

namespace Database\Seeders;

use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\ReminderPreference;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────────────────
        User::create([
            'name'     => 'Admin Strongmas',
            'email'    => 'admin@strongmas.ng',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // ── Properties + unit types ────────────────────────────────────────
        $elysian = Property::create([
            'code'             => 'STR-ELY-014',
            'development_name' => 'Elysian Rise',
            'address'          => 'Plot 14, Elysian Gardens, Lekki Phase 2, Lagos',
        ]);

        $elysianTerrace = PropertyUnit::create([
            'property_id'    => $elysian->id,
            'bedrooms'       => 3,
            'unit_type'      => 'terrace',
            'list_price_kobo'=> 3_250_000_00,
            'total_units'    => 20,
        ]);

        $elysianMaison = PropertyUnit::create([
            'property_id'    => $elysian->id,
            'bedrooms'       => 4,
            'unit_type'      => 'maisonette',
            'list_price_kobo'=> 4_500_000_00,
            'total_units'    => 12,
        ]);

        $elysianPent = PropertyUnit::create([
            'property_id'    => $elysian->id,
            'bedrooms'       => null,
            'unit_type'      => 'penthouse',
            'list_price_kobo'=> 6_000_000_00,
            'total_units'    => 4,
        ]);

        $kesbel = Property::create([
            'code'             => 'STR-KES-007',
            'development_name' => 'Kesbel Court',
            'address'          => '7 Kesbel Close, Ikeja GRA, Lagos',
        ]);

        $kesbelStudio = PropertyUnit::create([
            'property_id'    => $kesbel->id,
            'bedrooms'       => null,
            'unit_type'      => 'studio',
            'list_price_kobo'=> 850_000_00,
            'total_units'    => 10,
        ]);

        $kesbelApart = PropertyUnit::create([
            'property_id'    => $kesbel->id,
            'bedrooms'       => 2,
            'unit_type'      => 'apartment',
            'list_price_kobo'=> 1_500_000_00,
            'total_units'    => 8,
        ]);

        // ── Buyer: Adaeze Okonkwo ─────────────────────────────────────────
        $adaeze = User::create([
            'name'            => 'Adaeze Okonkwo',
            'email'           => 'adaeze@example.com',
            'password'        => Hash::make('password'),
            'phone'           => '+234 803 444 1209',
            'address'         => '45 Awolowo Road, Ikoyi, Lagos',
            'bvn_masked'      => '***-***-4521',
            'kyc_verified_at' => now()->subMonths(2),
            'is_admin'        => false,
        ]);

        ReminderPreference::create([
            'user_id'         => $adaeze->id,
            'in_app'          => true,
            'email'           => true,
            'sms'             => false,
            'days_before_due' => 3,
        ]);

        // ── Elysian Rise ownership for Adaeze (3BR Terrace) ───────────────
        $elysianOwnership = Ownership::create([
            'user_id'                  => $adaeze->id,
            'property_id'              => $elysian->id,
            'property_unit_id'         => $elysianTerrace->id,
            'agreed_price_kobo'        => 3_250_000_00,
            'payment_plan'             => 'instalment',
            'total_instalments'        => 24,
            'start_date'               => Carbon::now()->subMonths(14),
            'expected_completion_date' => Carbon::now()->addMonths(10),
            'status'                   => 'active',
        ]);

        $elysianMonthlyKobo = (int) round(3_250_000_00 / 24);
        $elysianPayments = [];
        for ($i = 1; $i <= 14; $i++) {
            $paidOn = Carbon::now()->subMonths(14 - $i)->startOfMonth()->addDays(3);
            $p = Payment::create([
                'ownership_id' => $elysianOwnership->id,
                'user_id'      => $adaeze->id,
                'amount_kobo'  => $elysianMonthlyKobo,
                'paid_on'      => $paidOn,
                'bank'         => $i % 3 === 0 ? 'GTBank' : ($i % 3 === 1 ? 'Access Bank' : 'Zenith Bank'),
                'reference'    => strtoupper('GTR' . str_pad($i * 97, 10, '0', STR_PAD_LEFT)),
            ]);
            $elysianPayments[] = $p;
        }

        for ($i = 1; $i <= 24; $i++) {
            $dueDate = Carbon::now()->subMonths(14 - $i)->startOfMonth();
            if ($i <= 14) {
                $status = 'paid';
                $paidId = $elysianPayments[$i - 1]->id;
            } elseif ($i === 15) {
                $status = 'due';
                $paidId = null;
            } else {
                $status = 'upcoming';
                $paidId = null;
            }
            Instalment::create([
                'ownership_id'      => $elysianOwnership->id,
                'instalment_number' => $i,
                'due_date'          => $dueDate,
                'amount_kobo'       => $elysianMonthlyKobo,
                'status'            => $status,
                'paid_payment_id'   => $paidId,
            ]);
        }

        // ── Kesbel Court ownership for Adaeze (Studio) ────────────────────
        $kesbelOwnership = Ownership::create([
            'user_id'                  => $adaeze->id,
            'property_id'              => $kesbel->id,
            'property_unit_id'         => $kesbelStudio->id,
            'agreed_price_kobo'        => 850_000_00,
            'payment_plan'             => 'instalment',
            'total_instalments'        => 12,
            'start_date'               => Carbon::now()->subMonths(4),
            'expected_completion_date' => Carbon::now()->addMonths(8),
            'status'                   => 'active',
        ]);

        $kesbelMonthlyKobo = (int) round(850_000_00 / 12);
        $kesbelPayments = [];
        for ($i = 1; $i <= 3; $i++) {
            $paidOn = Carbon::now()->subMonths(4 - $i)->startOfMonth()->addDays(5);
            $p = Payment::create([
                'ownership_id' => $kesbelOwnership->id,
                'user_id'      => $adaeze->id,
                'amount_kobo'  => $kesbelMonthlyKobo,
                'paid_on'      => $paidOn,
                'bank'         => 'First Bank',
                'reference'    => strtoupper('FBN' . str_pad($i * 43, 10, '0', STR_PAD_LEFT)),
            ]);
            $kesbelPayments[] = $p;
        }

        for ($i = 1; $i <= 12; $i++) {
            $dueDate = Carbon::now()->subMonths(4 - $i)->startOfMonth();
            if ($i <= 3) {
                $status = 'paid';
                $paidId = $kesbelPayments[$i - 1]->id;
            } elseif ($i === 4) {
                $status = 'due';
                $paidId = null;
            } else {
                $status = 'upcoming';
                $paidId = null;
            }
            Instalment::create([
                'ownership_id'      => $kesbelOwnership->id,
                'instalment_number' => $i,
                'due_date'          => $dueDate,
                'amount_kobo'       => $kesbelMonthlyKobo,
                'status'            => $status,
                'paid_payment_id'   => $paidId,
            ]);
        }

        // ── Buyer: Chukwuemeka Eze (Elysian 4BR Maisonette) ──────────────
        $emeka = User::create([
            'name'     => 'Chukwuemeka Eze',
            'email'    => 'emeka@example.com',
            'password' => Hash::make('password'),
            'phone'    => '+234 706 123 4567',
            'is_admin' => false,
        ]);

        ReminderPreference::create(['user_id' => $emeka->id]);

        $emekaOwnership = Ownership::create([
            'user_id'           => $emeka->id,
            'property_id'       => $elysian->id,
            'property_unit_id'  => $elysianMaison->id,
            'agreed_price_kobo' => 4_200_000_00,
            'payment_plan'      => 'instalment',
            'total_instalments' => 24,
            'start_date'        => Carbon::now()->subMonths(3),
            'status'            => 'active',
        ]);

        $emekaMonthlyKobo = (int) round(4_200_000_00 / 24);
        for ($i = 1; $i <= 2; $i++) {
            $paidOn = Carbon::now()->subMonths(3 - $i)->startOfMonth()->addDays(7);
            Payment::create([
                'ownership_id' => $emekaOwnership->id,
                'user_id'      => $emeka->id,
                'amount_kobo'  => $emekaMonthlyKobo,
                'paid_on'      => $paidOn,
                'bank'         => 'UBA',
                'reference'    => strtoupper('UBA' . str_pad($i * 61, 10, '0', STR_PAD_LEFT)),
            ]);
        }

        // ── Support messages for Adaeze ────────────────────────────────────
        $messages = [
            ['buyer', "Hello, I just made my instalment payment via GTBank but my balance hasn't updated yet. Can you check?", '-5 days'],
            ['agent', 'Hi Adaeze! Thanks for reaching out. Your payment has been received and is being verified. It should reflect within 24 hours.', '-4 days 20 hours'],
            ['buyer', 'Great, thank you! Also, is there any update on the construction progress at Elysian Rise?', '-4 days 18 hours'],
            ['agent', "Yes! The second floor slabs are complete and roofing starts next month. We'll send a site update shortly.", '-4 days 15 hours'],
            ['buyer', 'Perfect, thanks for the quick response!', '-4 days 14 hours'],
        ];

        foreach ($messages as [$sender, $body, $offset]) {
            SupportMessage::create([
                'user_id'    => $adaeze->id,
                'sender'     => $sender,
                'body'       => $body,
                'created_at' => Carbon::now()->modify($offset),
                'updated_at' => Carbon::now()->modify($offset),
            ]);
        }

        // ── Support ticket ─────────────────────────────────────────────────
        SupportTicket::create([
            'user_id'     => $adaeze->id,
            'property_id' => $elysian->id,
            'case_no'     => 'CASE-0001',
            'category'    => 'Balance not updated',
            'body'        => 'I transferred ₦1,354,167 on the 3rd of this month via GTBank (ref: GTR0000009). My balance still shows the old amount. Please update immediately.',
            'status'      => 'resolved',
            'created_at'  => Carbon::now()->subDays(5),
            'updated_at'  => Carbon::now()->subDays(4),
        ]);
    }
}
