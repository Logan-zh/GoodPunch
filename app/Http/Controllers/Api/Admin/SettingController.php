<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SettingController extends Controller
{
    #[OA\Get(
        path: '/api/admin/settings',
        summary: 'Get geofence settings',
        security: [['sanctum' => []]],
        tags: ['Admin / Settings'],
        responses: [
            new OA\Response(response: 200, description: 'Current settings'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'office_latitude' => Setting::get('office_latitude'),
            'office_longitude' => Setting::get('office_longitude'),
            'allowed_radius' => Setting::get('allowed_radius', 100),
            'company_name' => $request->user()->company?->name,
        ]);
    }

    #[OA\Put(
        path: '/api/admin/settings',
        summary: 'Update geofence settings',
        security: [['sanctum' => []]],
        tags: ['Admin / Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'office_latitude', type: 'number', nullable: true),
                    new OA\Property(property: 'office_longitude', type: 'number', nullable: true),
                    new OA\Property(property: 'allowed_radius', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Settings saved'),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'office_latitude' => 'nullable|numeric|between:-90,90',
            'office_longitude' => 'nullable|numeric|between:-180,180',
            'allowed_radius' => 'nullable|integer|min:0|max:50000',
        ]);

        Setting::set('office_latitude', $data['office_latitude']);
        Setting::set('office_longitude', $data['office_longitude']);
        Setting::set('allowed_radius', $data['allowed_radius']);

        return response()->json(['message' => '打卡範圍設定已儲存。']);
    }
}
