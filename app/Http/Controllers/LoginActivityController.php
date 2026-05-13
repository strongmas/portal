<?php

namespace App\Http\Controllers;

class LoginActivityController extends Controller
{
    public function index()
    {
        $activities = auth()->user()
            ->loginActivities()
            ->latest('logged_in_at')
            ->paginate(20);

        return view('login-activity.index', compact('activities'));
    }
}
