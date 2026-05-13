<?php

namespace App\Http\Controllers;

class MapController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $own    = $user->ownerships()->with(['property.images', 'unit'])->get();
        $member = $user->memberOwnerships()->with(['property.images', 'unit'])->get();

        $ownerships = $own->merge($member)->unique('id')
            ->filter(fn($o) => $o->property->lat && $o->property->lng)
            ->values();

        return view('map.index', compact('ownerships'));
    }
}
