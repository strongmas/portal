<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ownership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\SupportTicket;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $buyerCount      = User::where('is_admin', false)->count();
        $propertyCount   = Property::count();
        $ownershipCount  = Ownership::count();
        $totalCollected  = Payment::sum('amount_kobo');
        $totalOutstanding= Ownership::sum('agreed_price_kobo') - $totalCollected;
        $openTickets     = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
        $pendingKyc      = User::where('is_admin', false)->whereNull('kyc_verified_at')->count();

        $recentPayments = Payment::with(['user', 'ownership.property'])
            ->latest('paid_on')
            ->take(8)
            ->get();

        $recentBuyers = User::where('is_admin', false)
            ->latest()
            ->take(5)
            ->get();

        $recentTickets = SupportTicket::with('user')
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'buyerCount', 'propertyCount', 'ownershipCount',
            'totalCollected', 'totalOutstanding',
            'openTickets', 'pendingKyc',
            'recentPayments', 'recentBuyers', 'recentTickets'
        ));
    }
}
