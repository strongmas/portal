<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StaffWelcomeMail;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staff = User::where('is_admin', true)
            ->whereNotNull('admin_role_id')
            ->with('adminRole')
            ->latest()
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        $roles = AdminRole::orderBy('name')->get();
        return view('admin.staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users'],
            'admin_role_id' => ['required', 'exists:admin_roles,id'],
        ]);

        $password = Str::random(12);

        $staff = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'password'      => bcrypt($password),
            'is_admin'      => true,
            'admin_role_id' => $validated['admin_role_id'],
        ]);

        Mail::to($staff->email)->send(new StaffWelcomeMail($staff, $password));

        return redirect()->route('admin.staff.index')
            ->with('success', "Staff account created for {$staff->name}. Login credentials sent by email.");
    }

    public function edit(User $staff)
    {
        abort_unless($staff->isStaff(), 404);
        $roles = AdminRole::orderBy('name')->get();
        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, User $staff)
    {
        abort_unless($staff->isStaff(), 404);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', Rule::unique('users')->ignore($staff->id)],
            'admin_role_id' => ['required', 'exists:admin_roles,id'],
        ]);

        $staff->update($validated);

        return back()->with('success', 'Staff account updated.');
    }

    public function resetPassword(User $staff)
    {
        abort_unless($staff->isStaff(), 404);

        $password = Str::random(12);
        $staff->update(['password' => bcrypt($password)]);

        Mail::to($staff->email)->send(new StaffWelcomeMail($staff, $password, isReset: true));

        return back()->with('success', "Password reset and sent to {$staff->email}.");
    }

    public function destroy(User $staff)
    {
        abort_unless($staff->isStaff(), 404);
        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Staff account removed.');
    }
}
