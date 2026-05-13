<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InterestForwarded;
use App\Models\InterestRegistration;
use App\Models\SalesRep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminInterestController extends Controller
{
    public function index()
    {
        $interests = InterestRegistration::with(['buyer', 'property', 'unit', 'salesRep'])
            ->latest()
            ->paginate(30);

        $salesReps = SalesRep::where('is_active', true)->orderBy('name')->get();

        return view('admin.interests.index', compact('interests', 'salesReps'));
    }

    public function forward(Request $request, InterestRegistration $interest)
    {
        $request->validate([
            'sales_rep_id' => ['required', 'exists:sales_reps,id'],
        ]);

        $rep = SalesRep::findOrFail($request->sales_rep_id);

        $interest->update([
            'status'       => 'forwarded',
            'sales_rep_id' => $rep->id,
            'forwarded_by' => $request->user()->id,
            'forwarded_at' => now(),
        ]);

        Mail::to($rep->email)->send(new InterestForwarded($interest->fresh(['buyer', 'property', 'unit']), $rep));

        return back()->with('success', "Interest forwarded to {$rep->name}.");
    }
}
