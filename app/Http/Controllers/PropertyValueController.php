<?php

namespace App\Http\Controllers;

use App\Models\MarketInsight;
use App\Models\Ownership;
use App\Models\PropertyValueHistory;

class PropertyValueController extends Controller
{
    public function analyticsIndex()
    {
        $user = auth()->user();

        $ownerships = $user->ownerships()->with(['property', 'unit', 'payments'])->get();

        // Attach current value and gain data to each ownership
        $ownerships = $ownerships->map(function (Ownership $ownership) {
            $unit = $ownership->unit;
            $histories = $unit
                ? PropertyValueHistory::where('property_unit_id', $unit->id)
                    ->orderBy('recorded_at')
                    ->get()
                : collect();

            $purchaseKobo  = $ownership->agreed_price_kobo;
            $latestHistory = $histories->last();
            $currentKobo   = $latestHistory?->value_kobo ?? $purchaseKobo;
            $gainKobo      = $currentKobo - $purchaseKobo;
            $gainPercent   = $purchaseKobo > 0 ? round(($gainKobo / $purchaseKobo) * 100, 2) : 0;
            $hasHistory    = $histories->isNotEmpty();

            $ownership->analytics = compact('currentKobo', 'gainKobo', 'gainPercent', 'hasHistory');
            return $ownership;
        });

        return view('property-analytics.index', compact('ownerships'));
    }

    public function show(Ownership $ownership)
    {
        $this->authorize('view', $ownership);

        $unit = $ownership->unit;
        $histories = $unit
            ? PropertyValueHistory::where('property_unit_id', $unit->id)
                ->orderBy('recorded_at')
                ->get()
            : collect();

        return view('property-values.show', compact('ownership', 'histories'));
    }

    public function analytics(Ownership $ownership)
    {
        $this->authorize('view', $ownership);

        $ownership->load(['property', 'unit', 'payments']);

        $unit = $ownership->unit;
        $histories = $unit
            ? PropertyValueHistory::where('property_unit_id', $unit->id)
                ->orderBy('recorded_at')
                ->get()
            : collect();

        $purchaseKobo = $ownership->agreed_price_kobo;

        // Current value: prefer latest value history, then current_value_kobo, then purchase price
        $latestHistory  = $histories->last();
        $currentKobo    = $latestHistory?->value_kobo ?? $ownership->current_value_kobo ?? $purchaseKobo;
        $gainKobo       = $currentKobo - $purchaseKobo;
        $gainPercent    = $purchaseKobo > 0 ? round(($gainKobo / $purchaseKobo) * 100, 2) : 0;

        // Compound annual growth rate
        $purchaseDate = $ownership->purchase_date ?? $ownership->start_date;
        $years        = $purchaseDate
            ? max(0.083, $purchaseDate->diffInDays(now()) / 365)
            : null;
        $cagr         = ($years && $purchaseKobo > 0 && $currentKobo > 0)
            ? round((pow($currentKobo / $purchaseKobo, 1 / $years) - 1) * 100, 2)
            : null;

        $propertyInsights = MarketInsight::where('is_published', true)
            ->where('property_id', $ownership->property_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('property-analytics.show', compact(
            'ownership', 'histories', 'currentKobo', 'gainKobo', 'gainPercent', 'cagr', 'years', 'propertyInsights'
        ));
    }
}
