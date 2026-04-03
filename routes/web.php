<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('locale/{locale}', LocaleController::class)->name('locale');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/{payroll_record}/slip', [\App\Http\Controllers\PayrollController::class, 'slip'])->name('payroll.slip');

    Route::post('/punch', [\App\Http\Controllers\PunchController::class, 'store'])->name('punch.store');
    
    Route::get('/leave-requests', [\App\Http\Controllers\LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/leave-requests', [\App\Http\Controllers\LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::delete('/leave-requests/{leave_request}', [\App\Http\Controllers\LeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');

    Route::get('/punch-corrections', [\App\Http\Controllers\PunchCorrectionController::class, 'index'])->name('punch-corrections.index');
    Route::post('/punch-corrections', [\App\Http\Controllers\PunchCorrectionController::class, 'store'])->name('punch-corrections.store');
    Route::delete('/punch-corrections/{punch_correction}', [\App\Http\Controllers\PunchCorrectionController::class, 'destroy'])->name('punch-corrections.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'manager'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');

    // Global Attendance Logs
    Route::get('/attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export', [\App\Http\Controllers\Admin\AttendanceController::class, 'export'])->name('attendance.export');

    // Leave Policy Settings
    Route::get('/leave-policies', [\App\Http\Controllers\Admin\LeavePolicyController::class, 'index'])->name('leave-policies.index');
    Route::post('/leave-policies', [\App\Http\Controllers\Admin\LeavePolicyController::class, 'store'])->name('leave-policies.store');
    Route::patch('/leave-policies/settings', [\App\Http\Controllers\Admin\LeavePolicyController::class, 'updateCompanySettings'])->name('leave-policies.update-settings');
    Route::patch('/leave-policies/holidays', [\App\Http\Controllers\Admin\LeavePolicyController::class, 'updateHolidays'])->name('leave-policies.update-holidays');
    Route::delete('/leave-policies/{leave_policy}', [\App\Http\Controllers\Admin\LeavePolicyController::class, 'destroy'])->name('leave-policies.destroy');

    // Leave Approval Management
    Route::get('/leave-management', [\App\Http\Controllers\Admin\LeaveManagementController::class, 'index'])->name('leave-management.index');
    Route::patch('/leave-management/{leave_request}', [\App\Http\Controllers\Admin\LeaveManagementController::class, 'update'])->name('leave-management.update');

    // Punch Correction Management
    Route::get('/punch-corrections', [\App\Http\Controllers\Admin\PunchCorrectionController::class, 'index'])->name('punch-corrections.index');
    Route::patch('/punch-corrections/{punch_correction}', [\App\Http\Controllers\Admin\PunchCorrectionController::class, 'update'])->name('punch-corrections.update');

    // Payroll Management
    Route::get('/payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/calculate', [\App\Http\Controllers\Admin\PayrollController::class, 'calculate'])->name('payroll.calculate');
    Route::get('/payroll/salary-structures', [\App\Http\Controllers\Admin\PayrollController::class, 'salaryStructures'])->name('payroll.salary-structures');
    Route::post('/payroll/salary-structures', [\App\Http\Controllers\Admin\PayrollController::class, 'upsertSalaryStructure'])->name('payroll.upsert-salary-structure');
    Route::patch('/payroll/{payroll_record}/confirm', [\App\Http\Controllers\Admin\PayrollController::class, 'confirm'])->name('payroll.confirm');
    Route::get('/payroll/{payroll_record}/slip', [\App\Http\Controllers\Admin\PayrollController::class, 'adminSlip'])->name('payroll.admin-slip');

    // Punch Range Settings — accessible by managers (per-company scope via HasCompany trait)
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

    // System Settings (Restricted to Admin Only)
    Route::middleware('admin')->group(function () {

        // Enterprise Management
        Route::get('/companies', [\App\Http\Controllers\Admin\CompanyManagementController::class, 'index'])->name('companies.index');
        Route::delete('/companies/{company}', [\App\Http\Controllers\Admin\CompanyManagementController::class, 'destroy'])->name('companies.destroy');

        // Department Management
        Route::get('/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('departments.store');
        Route::patch('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('departments.destroy');
    });
});

require __DIR__.'/auth.php';
