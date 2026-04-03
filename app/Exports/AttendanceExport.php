<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\Punch;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int    $companyId,
        private readonly ?int   $userId,
        private readonly ?string $date,
    ) {}

    public function sheets(): array
    {
        $company = Company::find($this->companyId);

        // --- Punch records sheet ---
        $punches = Punch::with('user')
            ->where('company_id', $this->companyId)
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->date, fn($q) => $q->whereDate('punch_time', $this->date))
            ->latest()
            ->get();

        // --- Absence/late/early sheet ---
        $employees = User::where('company_id', $this->companyId)
            ->where('role', '!=', 'admin')
            ->when($this->userId, fn($q) => $q->where('id', $this->userId))
            ->select('id', 'name', 'email')
            ->get();

        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $today      = Carbon::now()->startOfDay();

        $workingDays = collect(CarbonPeriod::create($monthStart, $today))
            ->filter(fn($d) => $d->isWeekday())
            ->values();

        // Punch details per user → per date → first_in / last_out
        $punchDetails = Punch::where('company_id', $this->companyId)
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->whereBetween('punch_time', [$monthStart, $today->copy()->endOfDay()])
            ->get()
            ->groupBy('user_id')
            ->map(fn($userPunches) =>
                $userPunches
                    ->groupBy(fn($p) => $p->punch_time->toDateString())
                    ->map(fn($dayPunches) => [
                        'first_in' => $dayPunches->where('type', 'in')->sortBy('punch_time')->first()?->punch_time,
                        'last_out' => $dayPunches->where('type', 'out')->sortByDesc('punch_time')->first()?->punch_time,
                    ])
            );

        // Approved leave days per user
        $leaveDays = LeaveRequest::where('company_id', $this->companyId)
            ->where('status', 'approved')
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->where('start_at', '<=', $today->copy()->endOfDay())
            ->where('end_at', '>=', $monthStart)
            ->get()
            ->groupBy('user_id')
            ->map(function ($leaves) {
                $days = collect();
                foreach ($leaves as $leave) {
                    foreach (CarbonPeriod::create(
                        $leave->start_at->startOfDay(),
                        $leave->end_at->startOfDay()
                    ) as $day) {
                        $days->push($day->toDateString());
                    }
                }
                return $days->unique()->values();
            });

        return [
            new AttendancePunchSheet($punches),
            new AttendanceAbsenceSheet(
                $employees,
                $workingDays,
                $punchDetails,
                $leaveDays,
                $monthStart,
                $today,
                $company?->work_start_time ?? '09:00',
                $company?->work_end_time   ?? '18:00',
            ),
        ];
    }
}
