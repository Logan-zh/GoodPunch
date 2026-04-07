<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ApproverType;
use App\Http\Controllers\Controller;
use App\Models\LeavePolicy;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class LeavePolicyController extends Controller
{
    #[OA\Get(
        path: '/api/admin/leave-policies',
        summary: 'Get leave policies and company leave settings',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Policies'],
        responses: [
            new OA\Response(response: 200, description: 'Leave policies data'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $holidaysRaw = $company
            ? Setting::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->where('key', 'holidays')
                ->value('value')
            : null;

        return response()->json([
            'policies' => LeavePolicy::orderBy('type')->orderBy('min_years')->get(),
            'company' => $company,
            'holidays' => $holidaysRaw ? (json_decode($holidaysRaw, true) ?? []) : [],
            'availableApprovers' => $company
                ? User::where('company_id', $company->id)
                    ->whereIn('role', ['admin', 'manager'])
                    ->select('id', 'name')
                    ->get()
                : [],
            'approverTypes' => collect(ApproverType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label_key' => $t->labelKey(),
            ])->values(),
        ]);
    }

    #[OA\Post(
        path: '/api/admin/leave-policies',
        summary: 'Create or update a leave policy tier',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Policies'],
        responses: [
            new OA\Response(response: 200, description: 'Policy saved'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:annual,personal,sick',
            'min_years' => 'required|numeric|min:0',
            'days' => 'required|integer|min:0',
        ]);

        if (in_array($validated['type'], ['personal', 'sick'])) {
            $policy = LeavePolicy::updateOrCreate(
                ['type' => $validated['type']],
                ['days' => $validated['days'], 'min_years' => 0]
            );
        } else {
            $policy = LeavePolicy::updateOrCreate(
                ['type' => 'annual', 'min_years' => $validated['min_years']],
                ['days' => $validated['days']]
            );
        }

        return response()->json($policy);
    }

    #[OA\Put(
        path: '/api/admin/leave-policies/{id}',
        summary: 'Update a leave policy',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Policies'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
        ]
    )]
    public function update(Request $request, LeavePolicy $leavePolicy): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:annual,personal,sick',
            'min_years' => 'required|numeric|min:0',
            'days' => 'required|integer|min:0',
        ]);

        $leavePolicy->update($validated);

        return response()->json($leavePolicy);
    }

    #[OA\Delete(
        path: '/api/admin/leave-policies/{id}',
        summary: 'Delete a leave policy tier',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Policies'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
        ]
    )]
    public function destroy(LeavePolicy $leavePolicy): JsonResponse
    {
        $leavePolicy->delete();

        return response()->json(['message' => 'Leave policy tier removed.']);
    }

    #[OA\Put(
        path: '/api/admin/leave-policies/company-settings',
        summary: 'Update company work hours and leave approval chain',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Policies'],
        responses: [
            new OA\Response(response: 200, description: 'Settings saved'),
        ]
    )]
    public function updateCompanySettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_hours_per_day' => 'required|numeric|min:1|max:24',
            'work_start_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'work_end_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'leave_approval_chain' => 'nullable|array',
            'leave_approval_chain.*.type' => ['required', Rule::enum(ApproverType::class)],
            'leave_approval_chain.*.name' => 'required|string|max:50',
            'leave_approval_chain.*.user_id' => 'nullable|exists:users,id',
        ]);

        $company = $request->user()->company;

        if (! $company) {
            return response()->json(['message' => 'User is not associated with a company.'], 422);
        }

        $company->update($validated);

        return response()->json($company);
    }

    #[OA\Put(
        path: '/api/admin/leave-policies/holidays',
        summary: 'Update public holidays list',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Policies'],
        responses: [
            new OA\Response(response: 200, description: 'Holidays saved'),
        ]
    )]
    public function updateHolidays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'holidays' => 'required|array',
            'holidays.*' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        $company = $request->user()->company;

        if (! $company) {
            return response()->json(['message' => 'User is not associated with a company.'], 422);
        }

        Setting::withoutGlobalScope('company')->updateOrCreate(
            ['company_id' => $company->id, 'key' => 'holidays'],
            ['value' => json_encode(array_values(array_unique($validated['holidays'])))]
        );

        return response()->json(['message' => 'Holidays saved.', 'holidays' => $validated['holidays']]);
    }
}
