<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PunchCorrectionResource;
use App\Models\PunchCorrection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PunchCorrectionController extends Controller
{
    #[OA\Get(
        path: '/api/punch-corrections',
        summary: 'List punch correction requests for authenticated user',
        security: [['sanctum' => []]],
        tags: ['Punch Corrections'],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $corrections = PunchCorrection::where('user_id', $request->user()->id)
            ->with('approver')
            ->latest()
            ->paginate(15);

        return response()->json(PunchCorrectionResource::collection($corrections)->response()->getData(true));
    }

    #[OA\Post(
        path: '/api/punch-corrections',
        summary: 'Submit a punch correction request',
        security: [['sanctum' => []]],
        tags: ['Punch Corrections'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['correction_date', 'correction_type', 'requested_time'],
                properties: [
                    new OA\Property(property: 'correction_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'correction_type', type: 'string', enum: ['in', 'out', 'overtime_in', 'overtime_out']),
                    new OA\Property(property: 'requested_time', type: 'string', example: '09:00'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Correction submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'correction_date' => ['required', 'date'],
            'correction_type' => ['required', 'in:in,out,overtime_in,overtime_out'],
            'requested_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $correction = $request->user()->punchCorrections()->create([
            'correction_date' => $request->correction_date,
            'correction_type' => $request->correction_type,
            'requested_time' => $request->correction_date.' '.$request->requested_time.':00',
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json(new PunchCorrectionResource($correction), 201);
    }

    #[OA\Delete(
        path: '/api/punch-corrections/{id}',
        summary: 'Cancel a pending punch correction',
        security: [['sanctum' => []]],
        tags: ['Punch Corrections'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Cancelled'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Request $request, PunchCorrection $punchCorrection): JsonResponse
    {
        abort_unless(
            $punchCorrection->user_id === $request->user()->id && $punchCorrection->status === 'pending',
            403
        );

        $punchCorrection->delete();

        return response()->json(['message' => '補打卡申請已取消。']);
    }
}
