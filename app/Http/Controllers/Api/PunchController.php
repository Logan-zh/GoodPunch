<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PunchResource;
use App\Models\Setting;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PunchController extends Controller
{
    #[OA\Post(
        path: '/api/punches',
        summary: 'Record a punch (clock in/out)',
        security: [['sanctum' => []]],
        tags: ['Punches'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['in', 'out', 'overtime_in', 'overtime_out']),
                    new OA\Property(property: 'latitude', type: 'number', nullable: true),
                    new OA\Property(property: 'longitude', type: 'number', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Punch recorded'),
            new OA\Response(response: 422, description: 'Validation or location error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:in,out,overtime_in,overtime_out',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = $request->user();

        $offLat = Setting::get('office_latitude');
        $offLng = Setting::get('office_longitude');
        $allowedRadius = Setting::get('allowed_radius');

        if ($offLat && $offLng && $allowedRadius) {
            if (! $request->latitude || ! $request->longitude) {
                return response()->json(['message' => '打卡需要開啟定位權限，請允許傳送您的位置。'], 422);
            }

            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $offLat,
                $offLng
            );

            if ($distance > $allowedRadius) {
                return response()->json([
                    'message' => '您不在允許的打卡範圍內。目前距離辦公室：'.round($distance).' 公尺（限制：'.$allowedRadius.' 公尺）',
                ], 422);
            }
        }

        if (in_array($request->type, ['in', 'out'])) {
            $lastPunch = $user->punches()->whereIn('type', ['in', 'out'])->latest()->first();
            if ($lastPunch && $lastPunch->type === $request->type) {
                return response()->json(['message' => 'You have already punched '.$request->type], 422);
            }
        }

        $punch = $user->punches()->create([
            'type' => $request->type,
            'punch_time' => Carbon::now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        try {
            app(AttendanceService::class)->processDay($user, Carbon::today());
        } catch (\Throwable) {
            // Non-critical
        }

        return response()->json(new PunchResource($punch), 201);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
