<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(Request $request)
    {
        $year      = (int) ($request->year  ?? now()->year);
        $month     = (int) ($request->month ?? now()->month);
        $companyId = auth()->user()->company_id;

        $records = PayrollRecord::with('user')
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        $users = User::where('company_id', $companyId)
            ->where('role', '!=', 'admin')
            ->with('salaryStructure')
            ->get(['id', 'name', 'employee_id', 'position', 'department_id']);

        return Inertia::render('Admin/Payroll/Index', [
            'records' => $records,
            'users'   => $users,
            'year'    => $year,
            'month'   => $month,
            'filters' => $request->only(['year', 'month']),
        ]);
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $companyId = auth()->user()->company_id;
        $this->payrollService->calculateForCompany($companyId, (int) $request->year, (int) $request->month);

        return back()->with('success', '薪資結算完成。');
    }

    public function confirm(PayrollRecord $payrollRecord)
    {
        $payrollRecord->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        return back()->with('success', '薪資單已確認。');
    }

    public function adminSlip(PayrollRecord $payrollRecord)
    {
        $payrollRecord->load('user.company');

        return view('payroll.slip', ['record' => $payrollRecord]);
    }

    public function salaryStructures()
    {
        $companyId = auth()->user()->company_id;

        $users = User::where('company_id', $companyId)
            ->where('role', '!=', 'admin')
            ->with('salaryStructure')
            ->get(['id', 'name', 'employee_id', 'position']);

        return Inertia::render('Admin/Payroll/SalaryStructures', ['users' => $users]);
    }

    public function upsertSalaryStructure(Request $request)
    {
        $validated = $request->validate([
            'user_id'                   => 'required|exists:users,id',
            'base_salary'               => 'required|numeric|min:0',
            'meal_allowance'            => 'nullable|numeric|min:0',
            'perfect_attendance_bonus'  => 'nullable|numeric|min:0',
            'labor_insurance_employee'  => 'nullable|numeric|min:0',
            'health_insurance_employee' => 'nullable|numeric|min:0',
            'overtime_hourly_rate'      => 'nullable|numeric|min:0',
            'effective_from'            => 'nullable|date',
        ]);

        SalaryStructure::updateOrCreate(
            ['user_id' => $validated['user_id']],
            array_merge($validated, ['company_id' => auth()->user()->company_id])
        );

        return back()->with('success', '薪資結構已儲存。');
    }
}
