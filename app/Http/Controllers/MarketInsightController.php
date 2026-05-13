<?php

namespace App\Http\Controllers;

use App\Models\MarketInsight;

class MarketInsightController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get the property IDs this buyer has access to
        $propertyIds = $user->accessibleOwnerships()->pluck('property_id')->unique();

        $insights = MarketInsight::where('is_published', true)
            ->where(function ($q) use ($propertyIds) {
                // Show global insights (no property restriction) OR insights for buyer's properties
                $q->whereNull('property_id')
                  ->orWhereIn('property_id', $propertyIds);
            })
            ->latest()
            ->paginate(12);

        return view('market-insights.index', compact('insights'));
    }

    public function show(MarketInsight $marketInsight)
    {
        abort_unless($marketInsight->is_published, 404);
        return view('market-insights.show', compact('marketInsight'));
    }
}
