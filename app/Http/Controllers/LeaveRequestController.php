<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Enums\ApproverType;
use App\Models\Company;
use App\Models\LeaveApprovalStep;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        
        return Inertia::render('LeaveRequests/Index', [
            'requests' => LeaveRequest::where('user_id', $user->id)
                ->with(['steps.approver'])
                ->latest()
                ->get(),
            'leaveEntitlements' => $user->leave_entitlements,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:annual,personal,sick',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'reason' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['type' => 'Your account is not associated with a company. Please contact support.']);
        }

        $hours = $this->calculateHours($user, $validated['start_at'], $validated['end_at']);

        if ($hours <= 0) {
            return back()->withErrors(['start_at' => 'Total hours must be greater than 0. Check your dates/weekends.']);
        }

        // Check if user has enough balance
        $entitlements = $user->leave_entitlements;
        $typeRemainingHours = $entitlements[$validated['type'] . '_remaining_hours'] ?? 0;

        if ($hours > $typeRemainingHours) {
            return back()->withErrors(['type' => "Insufficient balance. Need {$hours}h but only have {$typeRemainingHours}h remaining."]);
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

            // Initialize Approval Steps based on Company Chain
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
                    'step_name' => $step['name'] ?? 'Approval Step ' . ($index + 1),
                    'status' => 'pending',
                ]);
            }
        });

        // Notify first approver (outside transaction so it only fires on commit)
        try {
            $firstApprover = $leaveRequest?->steps()
                ->where('step_index', 0)
                ->with('approver')
                ->first()
                ?->approver;

            if ($firstApprover && class_exists(\App\Notifications\LeaveRequestSubmitted::class)) {
                $firstApprover->notify(new \App\Notifications\LeaveRequestSubmitted($leaveRequest));
            }
        } catch (\Throwable) {
            // Notification failure must not break the request flow
        }

        return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted.');
    }

    private function calculateHours($user, $start, $end): float
    {
        $startAt = Carbon::parse($start);
        $endAt   = Carbon::parse($end);
        $company = $user->company;

        $workHoursPerDay = $company ? (float) $company->work_hours_per_day : 8.0;
        $workStartTime   = $company?->work_start_time ?? '09:00';
        $workEndTime     = $company?->work_end_time   ?? '18:00';

        [$wsHour, $wsMin] = array_map('intval', explode(':', $workStartTime));
        [$weHour, $weMin] = array_map('intval', explode(':', $workEndTime));

        $holidays    = $this->getHolidays($company);
        $totalHours  = 0.0;
        $current     = $startAt->copy()->startOfDay();

        while ($current->lessThan($endAt)) {
            $dateStr = $current->toDateString();

            if (!$current->isWeekend() && !in_array($dateStr, $holidays, true)) {
                $workStart = $current->copy()->setTime($wsHour, $wsMin);
                $workEnd   = $current->copy()->setTime($weHour, $weMin);

                // Intersect the leave window with the workday window
                $from = $startAt->greaterThan($workStart) ? $startAt->copy() : $workStart->copy();
                $to   = $endAt->lessThan($workEnd)         ? $endAt->copy()   : $workEnd->copy();

                if ($to->greaterThan($from)) {
                    $hours = $from->diffInMinutes($to) / 60.0;
                    $totalHours += min($hours, $workHoursPerDay);
                }
            }

            $current->addDay();
        }

        return round($totalHours, 2);
    }

    private function getHolidays(?Company $company): array
    {
        if (!$company) {
            return [];
        }

        $value = Setting::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('key', 'holidays')
            ->value('value');

        return $value ? (json_decode($value, true) ?? []) : [];
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->user_id !== auth()->user()?->id || $leaveRequest->status !== 'pending') {
            return back()->with('error', 'Unauthorized or already processed.');
        }

        $leaveRequest->delete();
        return back()->with('success', 'Request cancelled.');
    }
}
