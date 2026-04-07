<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\PunchResource;
use App\Models\Punch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    #[OA\Get(
        path: '/api/admin/attendance',
        summary: 'List punches with stats (filterable by user_id and date)',
        security: [['sanctum' => []]],
        tags: ['Admin / Attendance'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated punch list with stats'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        abort_if($companyId === null, 403, 'User is not associated with a company.');

        $today = Carbon::today();
        $totalStaff = User::where('company_id', $companyId)->where('role', '!=', 'admin')->count();
        $punchedInToday = Punch::where('company_id', $companyId)
            ->where('type', 'in')
            ->whereDate('punch_time', $today)
            ->distinct('user_id')
            ->count();
        $totalPunchesToday = Punch::whereDate('punch_time', $today)->count();

        $query = Punch::with('user')->latest();

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date) {
            $query->whereDate('punch_time', $request->date);
        }

        $punches = $query->paginate(15);

        return response()->json([
            'data' => PunchResource::collection($punches)->response()->getData(true),
            'stats' => [
                'totalStaff' => $totalStaff,
                'punchedInToday' => $punchedInToday,
                'totalPunchesToday' => $totalPunchesToday,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/admin/attendance/export',
        summary: 'Export attendance data as XLSX',
        security: [['sanctum' => []]],
        tags: ['Admin / Attendance'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'XLSX file download'),
        ]
    )]
    public function export(Request $request): BinaryFileResponse
    {
        $companyId = $request->user()->company_id;
        abort_if($companyId === null, 403, 'User is not associated with a company.');

        $filename = 'attendance_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new AttendanceExport($companyId, $request->user_id, $request->date),
            $filename
        );
    }
}
