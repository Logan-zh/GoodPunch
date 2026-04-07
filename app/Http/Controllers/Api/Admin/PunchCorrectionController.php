<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PunchCorrectionResource;
use App\Models\Punch;
use App\Models\PunchCorrection;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PunchCorrectionController extends Controller
{
    #[OA\Get(
        path: '/api/admin/punch-corrections',
        summary: 'List recent punch corrections for subordinates',
        security: [['sanctum' => []]],
        tags: ['Admin / Punch Corrections'],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $manager = $request->user();
        $subordinateIds = $manager->subordinates()->pluck('id');

        $corrections = PunchCorrection::whereIn('user_id', $subordinateIds)
            ->with(['user', 'approver'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->where('created_at', '>=', now()->subDays(30))
            ->paginate(20);

        return response()->json(PunchCorrectionResource::collection($corrections)->response()->getData(true));
    }

    #[OA\Put(
        path: '/api/admin/punch-corrections/{id}',
        summary: 'Approve or reject a punch correction',
        security: [['sanctum' => []]],
        tags: ['Admin / Punch Corrections'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['approved', 'rejected']),
                    new OA\Property(property: 'comment', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
        ]
    )]
    public function update(Request $request, PunchCorrection $punchCorrection): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->status === 'approved') {
            Punch::updateOrCreate(
                ['user_id' => $punchCorrection->user_id, 'correction_id' => $punchCorrection->id],
                [
                    'company_id' => $punchCorrection->company_id,
                    'type' => $punchCorrection->correction_type,
                    'punch_time' => $punchCorrection->requested_time,
                    'correction_id' => $punchCorrection->id,
                ]
            );

            try {
                app(AttendanceService::class)->processDay(
                    $punchCorrection->user,
                    Carbon::parse($punchCorrection->correction_date)
                );
            } catch (\Throwable) {
                // Non-critical
            }
        }

        $punchCorrection->update([
            'status' => $request->status,
            'approver_id' => $request->user()->id,
            'approved_at' => now(),
            'approval_note' => $request->comment,
        ]);

        return response()->json(new PunchCorrectionResource($punchCorrection->fresh('approver')));
    }
}
