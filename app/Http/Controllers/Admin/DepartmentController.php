<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $companyId = $currentUser->company_id;

        $departments = Department::where('company_id', $companyId)
            ->with(['children', 'manager', 'parent'])
            ->withCount('users')
            ->get();

        $managers = User::where('company_id', $companyId)
            ->whereIn('role', ['admin', 'manager', 'user'])
            ->get(['id', 'name']);

        $users = User::where('company_id', $companyId)
            ->get(['id', 'name', 'department_id']);

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'managers' => $managers,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        Department::create([
            'company_id' => $currentUser->company_id,
            'name' => $request->name,
            'parent_id' => $request->parent_id ?: null,
            'manager_id' => $request->manager_id ?: null,
        ]);

        return back()->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $department->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id ?: null,
            'manager_id' => $request->manager_id ?: null,
        ]);

        return back()->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Cannot delete department with assigned users. Please reassign them first.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted successfully.');
    }

    public function assignUser(Request $request, Department $department)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->user_id)
            ->where('company_id', $department->company_id)
            ->update(['department_id' => $department->id]);

        return back()->with('success', 'User assigned to department successfully.');
    }
}
