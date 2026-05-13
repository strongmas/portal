<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketInsight;
use App\Models\Property;
use Illuminate\Http\Request;

class AdminMarketInsightController extends Controller
{
    public function index()
    {
        $insights = MarketInsight::with('author')->latest()->paginate(20);
        return view('admin.market-insights.index', compact('insights'));
    }

    public function create()
    {
        $properties = Property::orderBy('development_name')->get();
        return view('admin.market-insights.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:200'],
            'region'         => ['required', 'string', 'max:100'],
            'category'       => ['required', 'in:pricing,demand,supply,rental,general'],
            'description'    => ['required', 'string'],
            'value'          => ['nullable', 'string', 'max:100'],
            'period'         => ['nullable', 'string', 'max:50'],
            'is_published'   => ['boolean'],
            'property_id'    => ['nullable', 'exists:properties,id'],
            'change_type'    => ['nullable', 'in:increase,decrease,stable'],
            'change_percent' => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        MarketInsight::create(array_merge($validated, ['created_by' => auth()->id()]));

        return redirect()->route('admin.market-insights.index')->with('success', 'Market insight published.');
    }

    public function edit(MarketInsight $marketInsight)
    {
        $properties = Property::orderBy('development_name')->get();
        return view('admin.market-insights.edit', compact('marketInsight', 'properties'));
    }

    public function update(Request $request, MarketInsight $marketInsight)
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:200'],
            'region'         => ['required', 'string', 'max:100'],
            'category'       => ['required', 'in:pricing,demand,supply,rental,general'],
            'description'    => ['required', 'string'],
            'value'          => ['nullable', 'string', 'max:100'],
            'period'         => ['nullable', 'string', 'max:50'],
            'is_published'   => ['boolean'],
            'property_id'    => ['nullable', 'exists:properties,id'],
            'change_type'    => ['nullable', 'in:increase,decrease,stable'],
            'change_percent' => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        $marketInsight->update($validated);

        return redirect()->route('admin.market-insights.index')->with('success', 'Insight updated.');
    }

    public function destroy(MarketInsight $marketInsight)
    {
        $marketInsight->delete();
        return back()->with('success', 'Insight deleted.');
    }
}
