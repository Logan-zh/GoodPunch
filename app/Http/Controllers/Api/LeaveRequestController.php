<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApproverType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Company;
use App\Models\LeaveApprovalStep;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class LeaveRequestController extends Controller
{
    #[OA\Get(
        path: '/api/leave-requests',
        summary: 'List leave requests for authenticated user',
        security: [['sanctum' => []]],
        tags: ['Leave Requests'],
        responses: [
            new OA\Response(response: 200, description: 'List of leave requests'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = LeaveRequest::where('user_id', $user->id)
            ->with(['steps.approver'])
            ->latest()
            ->get();

        return response()->json([
            'data' => LeaveRequestResource::collection($requests),
            'leave_entitlements' => $user->leave_entitlements,
        ]);
    }

    #[OA\Post(
        path: '/api/leave-requests',
        summary: 'Submit a leave request',
        security: [['sanctum' => []]],
        tags: ['Leave Requests'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'start_at', 'end_at'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['annual', 'personal', 'sick']),
                    new OA\Property(property: 'start_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'end_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Leave request submitted'),
            new OA\Response(response: 422, description: 'Validation error or insufficient balance'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:annual,personal,sick',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'reason' => 'nullable|string',
        ]);

        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            return response()->json(['message' => 'Your account is not associated with a company.'], 422);
        }

        $hours = $this->calculateHours($user, $validated['start_at'], $validated['end_at']);

        if ($hours <= 0) {
            return response()->json(['message' => 'Total hours must be greater than 0. Check your dates/weekends.'], 422);
        }

        $entitlements = $user->leave_entitlements;
        $typeRemainingHours = $entitlements[$validated['type'].'_remaining_hours'] ?? 0;

        if ($hours > $typeRemainingHours) {
            return response()->json([
                'message' => "Insufficient balance. Need {$hours}h but only have {$typeRemainingHours}h remaining.",
            ], 422);
        }

        $leaveRequest = null;

        DB::transaction(function () use ($user, $company, $validated, $hours, &$leaveRequest) {
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'type' => $validated['type'],
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
                'hours' => $hours,
                'reason' => $validated['reason'],
                'status' => 'pending',
                'current_step' => 0,
            ]);

            $chain = $company->leave_approval_chain ?? [['type' => ApproverType::Supervisor->value]];

            foreach ($chain as $index => $step) {
                $approverId = null;
                if ($step['type'] === ApproverType::Supervisor->value) {
                    $approverId = $user->supervisor_id;
                } elseif ($step['type'] === ApproverType::User->value) {
                    $approverId = $step['user_id'];
                }

                LeaveApprovalStep::create([
                    'leave_request_id' => $leaveRequest->id,
                    'approver_id' => $approverId,
                    'step_index' => $index,
                    'step_name' => $step['name'] ?? 'Approval Step '.($index + 1),
                    'status' => 'pending',
                ]);
            }
        });

        try {
            $firstApprover = $leaveRequest?->steps()
                ->where('step_index', 0)
                ->with('approver')
                ->first()
                ?->approver;

            if ($firstApprover) {
                $firstApprover->notify(new LeaveRequestSubmitted($leaveRequest));
            }
        } catch (\Throwable) {
            // Non-critical
        }

        return response()->json(new LeaveRequestResource($leaveRequest->load('steps')), 201);
    }

    #[OA\Delete(
        path: '/api/leave-requests/{id}',
        summary: 'Cancel a pending leave request',
        security: [['sanctum' => []]],
        tags: ['Leave Requests'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Cancelled'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        abort_unless(
            $leaveRequest->user_id === $request->user()->id && $leaveRequest->status === 'pending',
            403
        );

        $leaveRequest->delete();

        return response()->json(['message' => 'Leave request cancelled.']);
    }

    private function calculateHours($user, string $start, string $end): float
    {
        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);
        $company = $user->company;

        $workHoursPerDay = $company ? (float) $company->work_hours_per_day : 8.0;
        $workStartTime = $company?->work_start_time ?? '09:00';
        $workEndTime = $company?->work_end_time ?? '18:00';

        [$wsHour, $wsMin] = array_map('intval', explode(':', $workStartTime));
        [$weHour, $weMin] = array_map('intval', explode(':', $workEndTime));

        $holidays = $this->getHolidays($company);
        $totalHours = 0.0;
        $current = $startAt->copy()->startOfDay();

        while ($current->lessThan($endAt)) {
            $dateStr = $current->toDateString();

            if (! $current->isWeekend() && ! in_array($dateStr, $holidays, true)) {
                $workStart = $current->copy()->setTime($wsHour, $wsMin);
                $workEnd = $current->copy()->setTime($weHour, $weMin);
                $from = $startAt->greaterThan($workStart) ? $startAt->copy() : $workStart->copy();
                $to = $endAt->lessThan($workEnd) ? $endAt->copy() : $workEnd->copy();

                if ($to->greaterThan($from)) {
                    $totalHours += min($from->diffInMinutes($to) / 60.0, $workHoursPerDay);
                }
            }

            $current->addDay();
        }

        return round($totalHours, 2);
    }

    private function getHolidays(?Company $company): array
    {
        if (! $company) {
            return [];
        }

        $value = Setting::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('key', 'holidays')
            ->value('value');

        return $value ? (json_decode($value, true) ?? []) : [];
    }
}
