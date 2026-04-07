<?php

use App\Http\Controllers\Api\Admin\AttendanceController;
use App\Http\Controllers\Api\Admin\CompanyManagementController;
use App\Http\Controllers\Api\Admin\DepartmentController;
use App\Http\Controllers\Api\Admin\LeaveManagementController;
use App\Http\Controllers\Api\Admin\LeavePolicyController;
use App\Http\Controllers\Api\Admin\PayrollController as AdminPayrollController;
use App\Http\Controllers\Api\Admin\PunchCorrectionController as AdminPunchCorrectionController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PunchController;
use App\Http\Controllers\Api\PunchCorrectionController;
use App\Http\Controllers\Api\SwaggerController;
use Illuminate\Support\Facades\Route;

// API docs (public)
Route::get('/docs', [SwaggerController::class, 'ui']);
Route::get('/docs/openapi.json', [SwaggerController::class, 'spec']);

// Auth
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Employee routes
    Route::post('/punches', [PunchController::class, 'store']);

    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy']);

    Route::get('/punch-corrections', [PunchCorrectionController::class, 'index']);
    Route::post('/punch-corrections', [PunchCorrectionController::class, 'store']);
    Route::delete('/punch-corrections/{punchCorrection}', [PunchCorrectionController::class, 'destroy']);

    Route::get('/payroll', [PayrollController::class, 'index']);
    Route::get('/payroll/{payrollRecord}/slip', [PayrollController::class, 'slip']);

    // Manager/Admin routes
    Route::middleware('manager')->prefix('admin')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::get('/attendance/export', [AttendanceController::class, 'export']);

        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::put('/departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
        Route::post('/departments/{department}/assign', [DepartmentController::class, 'assignUser']);

        Route::get('/leave-management', [LeaveManagementController::class, 'index']);
        Route::put('/leave-management/{leaveRequest}', [LeaveManagementController::class, 'update']);

        Route::get('/punch-corrections', [AdminPunchCorrectionController::class, 'index']);
        Route::put('/punch-corrections/{punchCorrection}', [AdminPunchCorrectionController::class, 'update']);

        Route::get('/leave-policies', [LeavePolicyController::class, 'index']);
        Route::post('/leave-policies', [LeavePolicyController::class, 'store']);
        Route::put('/leave-policies/{leavePolicy}', [LeavePolicyController::class, 'update']);
        Route::delete('/leave-policies/{leavePolicy}', [LeavePolicyController::class, 'destroy']);
        Route::put('/leave-policies/company-settings', [LeavePolicyController::class, 'updateCompanySettings']);
        Route::put('/leave-policies/holidays', [LeavePolicyController::class, 'updateHolidays']);

        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);

        Route::get('/payroll', [AdminPayrollController::class, 'index']);
        Route::post('/payroll/calculate', [AdminPayrollController::class, 'calculate']);
        Route::put('/payroll/{payrollRecord}/confirm', [AdminPayrollController::class, 'confirm']);
        Route::get('/payroll/salary-structures', [AdminPayrollController::class, 'salaryStructures']);
        Route::post('/payroll/salary-structures', [AdminPayrollController::class, 'upsertSalaryStructure']);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
    });

    // Super admin only
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/companies', [CompanyManagementController::class, 'index']);
        Route::post('/companies', [CompanyManagementController::class, 'store']);
        Route::delete('/companies/{company}', [CompanyManagementController::class, 'destroy']);
    });
});
