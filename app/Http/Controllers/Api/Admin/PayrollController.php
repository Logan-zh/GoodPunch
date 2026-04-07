<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollRecordResource;
use App\Models\PayrollRecord;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    #[OA\Get(
        path: '/api/admin/payroll',
        summary: 'List payroll records for a given year/month',
        security: [['sanctum' => []]],
        tags: ['Admin / Payroll'],
        parameters: [
            new OA\Parameter(name: 'year', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'month', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payroll records'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);
        $companyId = $request->user()->company_id;

        $records = PayrollRecord::with('user')
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        $users = User::where('company_id', $companyId)
            ->where('role', '!=', 'admin')
            ->with('salaryStructure')
            ->get(['id', 'name', 'employee_id', 'position', 'department_id']);

        return response()->json([
            'records' => PayrollRecordResource::collection($records),
            'users' => $users,
            'year' => $year,
            'month' => $month,
        ]);
    }

    #[OA\Post(
        path: '/api/admin/payroll/calculate',
        summary: 'Calculate payroll for a given month',
        security: [['sanctum' => []]],
        tags: ['Admin / Payroll'],
        responses: [
            new OA\Response(response: 200, description: 'Payroll calculated'),
        ]
    )]
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        $companyId = $request->user()->company_id;
        $this->payrollService->calculateForCompany($companyId, (int) $request->year, (int) $request->month);

        return response()->json(['message' => '薪資結算完成。']);
    }

    #[OA\Put(
        path: '/api/admin/payroll/{id}/confirm',
        summary: 'Confirm a payroll record',
        security: [['sanctum' => []]],
        tags: ['Admin / Payroll'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Confirmed'),
        ]
    )]
    public function confirm(PayrollRecord $payrollRecord): JsonResponse
    {
        $payrollRecord->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        return response()->json(new PayrollRecordResource($payrollRecord));
    }

    #[OA\Get(
        path: '/api/admin/payroll/salary-structures',
        summary: 'List salary structures for all company employees',
        security: [['sanctum' => []]],
        tags: ['Admin / Payroll'],
        responses: [
            new OA\Response(response: 200, description: 'Users with salary structures'),
        ]
    )]
    public function salaryStructures(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $users = User::where('company_id', $companyId)
            ->where('role', '!=', 'admin')
            ->with('salaryStructure')
            ->get(['id', 'name', 'employee_id', 'position']);

        return response()->json($users);
    }

    #[OA\Post(
        path: '/api/admin/payroll/salary-structures',
        summary: 'Create or update a salary structure for a user',
        security: [['sanctum' => []]],
        tags: ['Admin / Payroll'],
        responses: [
            new OA\Response(response: 200, description: 'Salary structure saved'),
        ]
    )]
    public function upsertSalaryStructure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'perfect_attendance_bonus' => 'nullable|numeric|min:0',
            'labor_insurance_employee' => 'nullable|numeric|min:0',
            'health_insurance_employee' => 'nullable|numeric|min:0',
            'overtime_hourly_rate' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
        ]);

        $structure = SalaryStructure::updateOrCreate(
            ['user_id' => $validated['user_id']],
            array_merge($validated, ['company_id' => $request->user()->company_id])
        );

        return response()->json($structure);
    }
}
