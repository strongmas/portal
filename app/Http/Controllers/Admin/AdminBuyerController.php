<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BuyerPasswordResetMail;
use App\Mail\WelcomeBuyerMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\SalesRep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminBuyerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_admin', false)
            ->withCount('ownerships')
            ->with(['ownerships.property', 'ownerships.unit'])
            ->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $buyers = $query->paginate(20)->withQueryString();

        return view('admin.buyers.index', compact('buyers'));
    }

    public function show(User $buyer)
    {
        $buyer->load(['ownerships.property', 'ownerships.unit', 'ownerships.payments', 'supportTickets']);
        return view('admin.buyers.show', compact('buyer'));
    }

    public function create()
    {
        $properties = Property::with('units')->orderBy('development_name')->get();
        $units      = PropertyUnit::with('property')->get();
        $salesReps  = SalesRep::where('is_active', true)->orderBy('name')->get();
        return view('admin.buyers.create', compact('properties', 'units', 'salesReps'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'unique:users'],
            'phone'             => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'sales_rep_id'      => ['nullable', 'exists:sales_reps,id'],
            'property_id'       => ['required', 'exists:properties,id'],
            'property_unit_id'  => ['nullable', 'exists:property_units,id'],
            'agreed_price'      => ['required', 'string'],
            'payment_plan'          => ['required', 'in:outright,instalment'],
            'instalment_frequency'  => ['nullable', 'in:monthly,quarterly,bi-annually'],
            'total_instalments'     => ['nullable', 'integer', 'min:1'],
            'start_date'            => ['nullable', 'date'],
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'] ?? null,
            'sales_rep_id' => $validated['sales_rep_id'] ?? null,
            'password'     => Hash::make($tempPassword),
        ]);

        $ownership = Ownership::create([
            'user_id'           => $user->id,
            'property_id'       => $validated['property_id'],
            'property_unit_id'  => $validated['property_unit_id'] ?: null,
            'agreed_price_kobo'    => kobo($validated['agreed_price']),
            'payment_plan'         => $validated['payment_plan'],
            'instalment_frequency' => $validated['instalment_frequency'] ?? 'monthly',
            'total_instalments'    => $validated['total_instalments'] ?? 1,
            'start_date'           => $validated['start_date'] ?? now(),
            'status'               => 'active',
        ]);

        if ($validated['payment_plan'] === 'instalment') {
            $this->generateInstalments($ownership);
        }

        // Build property name for welcome email
        $propertyName = null;
        if ($validated['property_unit_id']) {
            $unit = PropertyUnit::with('property')->find($validated['property_unit_id']);
            if ($unit) {
                $propertyName = $unit->property->development_name . ' · ' . $unit->label;
            }
        } else {
            $prop         = Property::find($validated['property_id']);
            $propertyName = $prop?->development_name;
        }

        // Send welcome email — catch any SMTP error so it doesn't break buyer creation
        $mailSent = false;
        try {
            Mail::to($user->email)->send(new WelcomeBuyerMail($user, $tempPassword, $propertyName));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error('WelcomeBuyerMail failed for user #' . $user->id . ': ' . $e->getMessage());
        }

        $message = "Buyer account created. Temp password: {$tempPassword}";
        if (!$mailSent) {
            $message .= ' (Welcome email could not be sent — check your SMTP settings.)';
        }

        return redirect()->route('admin.buyers.show', $user)->with('success', $message);
    }

    public function edit(User $buyer)
    {
        $properties = Property::with('units')->orderBy('development_name')->get();
        return view('admin.buyers.edit', compact('buyer', 'properties'));
    }

    public function update(Request $request, User $buyer)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'kyc_verified' => ['boolean'],
        ]);

        $buyer->update([
            'name'            => $validated['name'],
            'phone'           => $validated['phone'] ?? null,
            'kyc_verified_at' => $request->boolean('kyc_verified') ? now() : null,
        ]);

        return back()->with('success', 'Buyer updated.');
    }

    public function resetPassword(User $buyer)
    {
        $tempPassword = Str::random(12);

        $buyer->update(['password' => Hash::make($tempPassword)]);

        $mailSent = false;
        try {
            Mail::to($buyer->email)->send(new BuyerPasswordResetMail($buyer, $tempPassword));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error('BuyerPasswordResetMail failed for user #' . $buyer->id . ': ' . $e->getMessage());
        }

        $message = "Password reset. New temp password: {$tempPassword}";
        if (!$mailSent) {
            $message .= ' (Email could not be sent — give this password to the buyer manually.)';
        }

        return back()->with('success', $message);
    }

    private function generateInstalments(Ownership $ownership): void
    {
        $total      = (int) $ownership->agreed_price_kobo;
        $count      = (int) $ownership->total_instalments;
        $amountEach = (int) floor($total / $count);
        $startDate  = ($ownership->start_date ?? now())->copy();
        $today      = now()->startOfDay();
        $firstMark  = false;
        $interval   = match ($ownership->instalment_frequency ?? 'monthly') {
            'quarterly'   => 3,
            'bi-annually' => 6,
            default       => 1,
        };
        $rows       = [];

        for ($i = 1; $i <= $count; $i++) {
            $dueDate = $startDate->copy()->addMonths(($i - 1) * $interval);
            $isLast  = ($i === $count);
            $amount  = $isLast ? max(0, $total - ($amountEach * ($count - 1))) : $amountEach;

            if (!$firstMark) {
                $status    = 'due';
                $firstMark = true;
            } elseif ($dueDate->lt($today)) {
                $status = 'overdue';
            } else {
                $status = 'upcoming';
            }

            $rows[] = [
                'ownership_id'      => $ownership->id,
                'instalment_number' => $i,
                'due_date'          => $dueDate->toDateString(),
                'amount_kobo'       => $amount,
                'status'            => $status,
                'paid_payment_id'   => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        Instalment::insert($rows);
    }
}
