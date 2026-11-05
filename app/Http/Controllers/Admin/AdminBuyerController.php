<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BuyerPasswordResetMail;
use App\Mail\WelcomeBuyerMail;
use App\Models\Instalment;
use App\Models\Ownership;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyUnit;
use App\Models\SalesRep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminBuyerController extends Controller
{
    public function index(Request $request)
    {
        $showArchived  = $request->boolean('archived');
        $archivedCount = User::where('is_admin', false)->whereNotNull('archived_at')->count();

        $query = User::where('is_admin', false)
            ->when($showArchived, fn($q) => $q->whereNotNull('archived_at'), fn($q) => $q->whereNull('archived_at'))
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

        return view('admin.buyers.index', compact('buyers', 'showArchived', 'archivedCount'));
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

    /** Hides the buyer and blocks their login. Nothing is erased. */
    public function archive(Request $request, User $buyer)
    {
        abort_if($buyer->is_admin, 404);

        $buyer->forceFill(['archived_at' => now()])->save();

        Log::info('Buyer archived', ['admin_id' => $request->user()->id, 'buyer_id' => $buyer->id]);

        return back()->with('success', "{$buyer->name} has been archived and can no longer log in.");
    }

    public function restore(Request $request, User $buyer)
    {
        abort_if($buyer->is_admin, 404);

        $buyer->forceFill(['archived_at' => null])->save();

        Log::info('Buyer restored', ['admin_id' => $request->user()->id, 'buyer_id' => $buyer->id]);

        return back()->with('success', "{$buyer->name} has been restored and can log in again.");
    }

    /**
     * Permanently erases a buyer. Foreign keys cascade to their property links,
     * instalments, tickets and chats, so this is refused for anyone with payment
     * records: those must be archived instead.
     */
    public function destroy(Request $request, User $buyer)
    {
        abort_if($buyer->is_admin, 404);

        if ($buyer->hasPaymentRecords()) {
            return back()->with('error', "{$buyer->name} has payment records, so they can only be archived.");
        }

        if (trim((string) $request->input('confirm_name')) !== $buyer->name) {
            return back()->with('error', 'The name you typed did not match, so nothing was deleted.');
        }

        $privateFiles = $buyer->supportMessages()->whereNotNull('attachment_path')->pluck('attachment_path')
            ->merge(PropertyDocument::where('uploaded_by', $buyer->id)->pluck('file_path'));
        $avatar = $buyer->avatar_path;
        $name   = $buyer->name;

        $buyer->delete();

        Log::warning('Buyer permanently deleted', [
            'admin_id' => $request->user()->id,
            'buyer_id' => $buyer->id,
            'email'    => $buyer->email,
        ]);

        // A storage outage must not undo a completed deletion: leave any orphans behind.
        try {
            Storage::disk('private')->delete($privateFiles->all());
            if ($avatar) {
                Storage::disk('public')->delete($avatar);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.buyers.index')->with('success', "{$name} has been permanently deleted.");
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
