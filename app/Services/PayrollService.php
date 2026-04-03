<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\SalaryStructure;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollService
{
    /**
     * Calculate payroll for a single user for the given year + month.
     * Returns the upserted PayrollRecord, or null if no salary structure exists.
     */
    public function calculateForUser(User $user, int $year, int $month): ?PayrollRecord
    {
        $structure = SalaryStructure::where('user_id', $user->id)
            ->latest('effective_from')
            ->first();

        if (! $structure) {
            return null;
        }

        $user->loadMissing('company');
        $workHoursPerDay = (float) ($user->company?->work_hours_per_day ?? 8);

        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $workingDays = $this->getWorkingDays($year, $month, $user->company_id);

        $baseSalary = (float) $structure->base_salary;
        $dailyRate  = $workingDays > 0 ? $baseSalary / $workingDays : 0;
        $hourlyRate = $workHoursPerDay > 0 ? $dailyRate / $workHoursPerDay : 0;
        $minuteRate = $hourlyRate / 60;

        // Late deduction: sum of late_minutes × per-minute rate
        $totalLateMinutes = (int) $attendanceRecords->sum('late_minutes');
        $lateDeduction    = round($totalLateMinutes * $minuteRate, 2);

        // Personal leave deduction: approved personal leave starting in this month
        $personalLeaveHours = (float) LeaveRequest::where('user_id', $user->id)
            ->where('type', 'personal')
            ->where('status', 'approved')
            ->whereYear('start_at', $year)
            ->whereMonth('start_at', $month)
            ->sum('hours');
        $leaveDeduction = round($personalLeaveHours * $hourlyRate, 2);

        // Overtime pay
        $totalOvertimeHours = (float) $attendanceRecords->sum('overtime_hours');
        $overtimePay        = round($totalOvertimeHours * (float) $structure->overtime_hourly_rate, 2);

        // Perfect attendance bonus: zero late minutes AND no personal leave this month
        $perfectAttendanceBonus = ($totalLateMinutes === 0 && $personalLeaveHours == 0)
            ? (float) $structure->perfect_attendance_bonus
            : 0.0;

        $grossPay = round(
            $baseSalary
            + (float) $structure->meal_allowance
            + $perfectAttendanceBonus
            + $overtimePay
            - $lateDeduction
            - $leaveDeduction,
            2
        );

        $netPay = round(
            $grossPay
            - (float) $structure->labor_insurance_employee
            - (float) $structure->health_insurance_employee,
            2
        );

        return PayrollRecord::updateOrCreate(
            ['user_id' => $user->id, 'year' => $year, 'month' => $month],
            [
                'company_id'               => $user->company_id,
                'base_salary'              => $baseSalary,
                'meal_allowance'           => (float) $structure->meal_allowance,
                'perfect_attendance_bonus' => $perfectAttendanceBonus,
                'overtime_pay'             => $overtimePay,
                'late_deduction'           => $lateDeduction,
                'leave_deduction'          => $leaveDeduction,
                'labor_insurance'          => (float) $structure->labor_insurance_employee,
                'health_insurance'         => (float) $structure->health_insurance_employee,
                'gross_pay'                => $grossPay,
                'net_pay'                  => $netPay,
                'status'                   => 'draft',
            ]
        );
    }

    /**
     * Calculate payroll for all non-admin users in a company.
     */
    public function calculateForCompany(int $companyId, int $year, int $month): Collection
    {
        $users = User::where('company_id', $companyId)
            ->where('role', '!=', 'admin')
            ->with('company')
            ->get();

        return $users
            ->map(fn (User $user) => $this->calculateForUser($user, $year, $month))
            ->filter();
    }

    /**
     * Count working days (non-weekend, non-holiday) for a month.
     */
    private function getWorkingDays(int $year, int $month, ?int $companyId = null): int
    {
        $holidays = [];

        if ($companyId) {
            $raw = Setting::withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('key', 'holidays')
                ->value('value');

            if ($raw) {
                $holidays = json_decode($raw, true) ?? [];
            }
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();
        $count = 0;

        while ($start->lte($end)) {
            if (! $start->isWeekend() && ! in_array($start->toDateString(), $holidays)) {
                $count++;
            }
            $start->addDay();
        }

        return $count;
    }
}
