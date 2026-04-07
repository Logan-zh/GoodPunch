<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestStatusChanged;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class LeaveManagementController extends Controller
{
    #[OA\Get(
        path: '/api/admin/leave-management',
        summary: 'List pending leave requests awaiting current manager approval',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Management'],
        responses: [
            new OA\Response(response: 200, description: 'List of pending requests'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $pendingRequests = LeaveRequest::with(['user', 'steps.approver'])
            ->where('status', 'pending')
            ->whereHas('steps', function ($query) use ($currentUser) {
                $query->where('approver_id', $currentUser->id)->where('status', 'pending');
            })
            ->latest()
            ->get()
            ->filter(fn ($req) => $req->steps
                ->where('step_index', $req->current_step)
                ->where('approver_id', $currentUser->id)
                ->isNotEmpty()
            );

        return response()->json(LeaveRequestResource::collection($pendingRequests->values()));
    }

    #[OA\Put(
        path: '/api/admin/leave-management/{id}',
        summary: 'Approve or reject a leave request step',
        security: [['sanctum' => []]],
        tags: ['Admin / Leave Management'],
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
            new OA\Response(response: 403, description: 'Not the current approver'),
        ]
    )]
    public function update(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'comment' => 'nullable|string',
        ]);

        $currentUser = $request->user();
        $currentStep = $leaveRequest->steps()->where('step_index', $leaveRequest->current_step)->first();

        if (! $currentStep || $currentStep->approver_id !== $currentUser->id) {
            return response()->json(['message' => 'You are not the authorized approver for the current step.'], 403);
        }

        DB::transaction(function () use ($leaveRequest, $currentStep, $validated) {
            $currentStep->update([
                'status' => $validated['status'],
                'comment' => $validated['comment'],
                'action_at' => now(),
            ]);

            if ($validated['status'] === 'rejected') {
                $leaveRequest->update(['status' => 'rejected']);
            } else {
                $nextExists = $leaveRequest->steps()->where('step_index', $leaveRequest->current_step + 1)->exists();
                if ($nextExists) {
                    $leaveRequest->increment('current_step');
                } else {
                    $leaveRequest->update(['status' => 'approved']);
                }
            }
        });

        try {
            $fresh = $leaveRequest->fresh(['user', 'steps.approver']);
            if ($validated['status'] === 'rejected' || $fresh->status === 'approved') {
                $fresh->user->notify(new LeaveRequestStatusChanged(
                    $fresh,
                    $fresh->status,
                    $validated['comment'] ?? null
                ));
            } else {
                $nextApprover = $fresh->steps->firstWhere('step_index', $fresh->current_step)?->approver;
                if ($nextApprover) {
                    $nextApprover->notify(new LeaveRequestSubmitted($fresh));
                }
            }
        } catch (\Throwable) {
            // Non-critical
        }

        return response()->json(new LeaveRequestResource($leaveRequest->fresh('steps')));
    }
}
