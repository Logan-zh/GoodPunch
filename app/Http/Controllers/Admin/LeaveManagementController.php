<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestStatusChanged;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaveManagementController extends Controller
{
    public function index()
    {
        // Managers/Admins can see requests where they are the current approver
        $currentUser = Auth::user();

        if ($response = $this->redirectIfCompanyIsMissing($currentUser)) {
            return $response;
        }

        $pendingRequests = LeaveRequest::with(['user', 'steps.approver'])
            ->where('status', 'pending')
            ->whereHas('steps', function ($query) use ($currentUser) {
                $query->where('approver_id', $currentUser->id)
                    ->where('status', 'pending');
            })
            ->latest()
            ->get()
            ->filter(function ($request) use ($currentUser) {
                // Only show if it's the current step for this user
                return $request->steps->where('step_index', $request->current_step)
                    ->where('approver_id', $currentUser->id)
                    ->isNotEmpty();
            });

        return Inertia::render('Admin/LeaveRequests/Index', [
            'requests' => $pendingRequests->values(),
        ]);
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'comment' => 'nullable|string',
        ]);

        $currentUser = Auth::user();

        if ($response = $this->redirectIfCompanyIsMissing($currentUser)) {
            return $response;
        }

        $currentStep = $leaveRequest->steps()->where('step_index', $leaveRequest->current_step)->first();

        if (! $currentStep || $currentStep->approver_id !== $currentUser->id) {
            return back()->with('error', 'You are not the authorized approver for the current step.');
        }

        DB::transaction(function () use ($leaveRequest, $currentStep, $validated) {
            // Update current step
            $currentStep->update([
                'status' => $validated['status'],
                'comment' => $validated['comment'],
                'action_at' => now(),
            ]);

            if ($validated['status'] === 'rejected') {
                $leaveRequest->update(['status' => 'rejected']);
            } else {
                // If this was the last step, approve the whole request
                $nextStepExists = $leaveRequest->steps()->where('step_index', $leaveRequest->current_step + 1)->exists();

                if ($nextStepExists) {
                    $leaveRequest->increment('current_step');
                } else {
                    $leaveRequest->update(['status' => 'approved']);
                }
            }
        });

        // Send notifications after transaction commits
        try {
            $fresh = $leaveRequest->fresh(['user', 'steps.approver']);

            if ($validated['status'] === 'rejected') {
                // Notify the employee their request was rejected
                $fresh->user->notify(new LeaveRequestStatusChanged(
                    $fresh,
                    'rejected',
                    $validated['comment'] ?? null
                ));
            } elseif ($fresh->status === 'approved') {
                // Final approval — notify the employee
                $fresh->user->notify(new LeaveRequestStatusChanged(
                    $fresh,
                    'approved',
                    $validated['comment'] ?? null
                ));
            } else {
                // Intermediate approval — notify the next approver
                $nextApprover = $fresh->steps
                    ->firstWhere('step_index', $fresh->current_step)
                    ?->approver;

                $nextApprover?->notify(new LeaveRequestSubmitted($fresh));
            }
        } catch (\Throwable) {
            // Notification failure must not break the approval flow
        }

        return back()->with('success', 'Leave request updated.');
    }

    private function redirectIfCompanyIsMissing(User $user): ?RedirectResponse
    {
        if ($user->company_id !== null) {
            return null;
        }

        return redirect()->route('dashboard')->with('alert', [
            'type' => 'error',
            'title' => '無法進入請假審核',
            'message' => '目前帳號尚未綁定公司，因此無法查看請假審核。',
        ]);
    }
}
