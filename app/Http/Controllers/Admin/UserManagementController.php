<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        $companyId = $currentUser->company_id;
        
        return Inertia::render('Admin/Users/Index', [
            'users' => User::where('company_id', $companyId)
                ->with('supervisor')
                ->latest()
                ->paginate(10)
                ->through(fn ($user) => $user->append('leave_entitlements')),
            'supervisors' => User::where('company_id', $companyId)
                ->whereIn('role', ['admin', 'manager'])
                ->get(['id', 'name']),
            'departments' => Department::where('company_id', $companyId)
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:admin,manager,user',
            'hired_at' => 'nullable|date',
            'supervisor_id' => 'nullable|exists:users,id',
            'employee_id' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'hired_at' => $request->hired_at,
            'supervisor_id' => $request->supervisor_id,
            'employee_id' => $request->employee_id,
            'position' => $request->position,
            'department_id' => $request->department_id,
            'company_id' => auth()->user()->company_id,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,manager,user',
            'hired_at' => 'nullable|date',
            'supervisor_id' => 'nullable|exists:users,id',
            'employee_id' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'hired_at' => $request->hired_at,
            'supervisor_id' => $request->supervisor_id,
            'employee_id' => $request->employee_id,
            'position' => $request->position,
            'department_id' => $request->department_id,
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password reset successfully for ' . $user->name);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
