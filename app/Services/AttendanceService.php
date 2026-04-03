<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\Punch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * Process attendance for a single user on a given date.
     */
    public function processDay(User $user, Carbon $date): AttendanceRecord
    {
        $company = $user->company;

        $punches = Punch::where('user_id', $user->id)
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time')
            ->get();

        $clockIn     = $punches->firstWhere('type', 'in')?->punch_time;
        $clockOut    = $punches->last(fn ($p) => $p->type === 'out')?->punch_time;
        $overtimeIn  = $punches->firstWhere('type', 'overtime_in')?->punch_time;
        $overtimeOut = $punches->last(fn ($p) => $p->type === 'overtime_out')?->punch_time;

        $onLeave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_at', '<=', $date->toDateString())
            ->whereDate('end_at', '>=', $date->toDateString())
            ->exists();

        $isWeekend = in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

        $lateMinutes       = 0;
        $earlyLeaveMinutes = 0;
        $overtimeHours     = 0.0;
        $status            = 'normal';

        if ($onLeave) {
            $status = 'on_leave';
        } elseif (! $clockIn && ! $isWeekend) {
            $status = 'absent';
        } else {
            if ($clockIn && $company->work_start_time) {
                $workStart = Carbon::parse($date->toDateString() . ' ' . $company->work_start_time);
                $diff = $clockIn->diffInMinutes($workStart, false);
                if ($diff < -5) {
                    $lateMinutes = (int) abs($diff);
                }
            }

            if ($clockOut && $company->work_end_time) {
                $workEnd = Carbon::parse($date->toDateString() . ' ' . $company->work_end_time);
                $diff = $workEnd->diffInMinutes($clockOut, false);
                if ($diff > 5) {
                    $earlyLeaveMinutes = (int) $diff;
                }
            }

            $isLate       = $lateMinutes > 0;
            $isEarlyLeave = $earlyLeaveMinutes > 0;

            if ($isLate && $isEarlyLeave) {
                $status = 'late_and_early';
            } elseif ($isLate) {
                $status = 'late';
            } elseif ($isEarlyLeave) {
                $status = 'early_leave';
            } else {
                $status = 'normal';
            }
        }

        if ($overtimeIn && $overtimeOut && $overtimeOut->gt($overtimeIn)) {
            $overtimeHours = round($overtimeIn->diffInMinutes($overtimeOut) / 60, 2);
        }

        return AttendanceRecord::updateOrCreate(
            ['user_id' => $user->id, 'date' => $date->toDateString()],
            [
                'company_id'          => $user->company_id,
                'clock_in'            => $clockIn,
                'clock_out'           => $clockOut,
                'overtime_in'         => $overtimeIn,
                'overtime_out'        => $overtimeOut,
                'status'              => $status,
                'late_minutes'        => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_hours'      => $overtimeHours,
            ]
        );
    }

    /**
     * Process all users for a given date.
     */
    public function processAllForDate(Carbon $date): void
    {
        User::with('company')->chunk(100, function ($users) use ($date) {
            foreach ($users as $user) {
                try {
                    $this->processDay($user, $date);
                } catch (\Throwable $e) {
                    Log::error("AttendanceService: failed to process user {$user->id} on {$date->toDateString()}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
