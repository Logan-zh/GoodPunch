<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollRecordResource;
use App\Models\PayrollRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PayrollController extends Controller
{
    #[OA\Get(
        path: '/api/payroll',
        summary: 'List confirmed payroll records for authenticated user',
        security: [['sanctum' => []]],
        tags: ['Payroll'],
        responses: [
            new OA\Response(response: 200, description: 'List of payroll records'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $records = PayrollRecord::where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json(PayrollRecordResource::collection($records));
    }

    #[OA\Get(
        path: '/api/payroll/{id}/slip',
        summary: 'Get payroll slip data for a specific record',
        security: [['sanctum' => []]],
        tags: ['Payroll'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Payroll slip data'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function slip(Request $request, PayrollRecord $payrollRecord): JsonResponse
    {
        abort_if($payrollRecord->user_id !== $request->user()->id, 403);
        abort_if($payrollRecord->status !== 'confirmed', 403);

        $payrollRecord->load('user.company');

        return response()->json(new PayrollRecordResource($payrollRecord));
    }
}
