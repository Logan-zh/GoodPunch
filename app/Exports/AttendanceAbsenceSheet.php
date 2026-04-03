<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendanceAbsenceSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly Collection $employees,
        private readonly Collection $workingDays,
        private readonly Collection $punchDetails,   // user_id → date → [first_in, last_out]
        private readonly Collection $leaveDays,      // user_id → date[]
        private readonly Carbon     $monthStart,
        private readonly Carbon     $today,
        private readonly string     $workStartTime,  // e.g. "09:00"
        private readonly string     $workEndTime,    // e.g. "18:00"
    ) {}

    public function title(): string
    {
        return '缺席統計';
    }

    public function headings(): array
    {
        $period = $this->monthStart->format('Y/m/d') . ' — ' . $this->today->format('Y/m/d')
            . '  （上班：' . $this->workStartTime . '  下班：' . $this->workEndTime . '）';

        return [
            [$period, '', '', '', '', '', '', '', ''],
            ['員工姓名', '電子郵件', '統計工作天', '請假天數', '實際出勤天', '缺席天數', '遲到次數', '早退次數', '出勤率'],
        ];
    }

    public function collection(): Collection
    {
        $totalWorkingDays = $this->workingDays->count();

        return $this->employees->map(function ($employee) use ($totalWorkingDays) {
            $userPunches   = $this->punchDetails->get($employee->id, collect());
            $userLeaveDays = $this->leaveDays->get($employee->id, collect());

            $presentCount = 0;
            $leaveDayCount = 0;
            $lateCount    = 0;
            $earlyCount   = 0;

            foreach ($this->workingDays as $day) {
                $dateStr = $day->toDateString();

                // Day covered by approved leave → skip late/absent checks
                if ($userLeaveDays->contains($dateStr)) {
                    $leaveDayCount++;
                    continue;
                }

                $dayData = $userPunches->get($dateStr);

                if (! $dayData) {
                    // No punch at all → absent (counted below)
                    continue;
                }

                $presentCount++;

                // Late check
                if ($dayData['first_in']) {
                    $threshold = Carbon::parse($dateStr . ' ' . $this->workStartTime);
                    if ($dayData['first_in']->gt($threshold)) {
                        $lateCount++;
                    }
                }

                // Early departure check
                if ($dayData['last_out']) {
                    $threshold = Carbon::parse($dateStr . ' ' . $this->workEndTime);
                    if ($dayData['last_out']->lt($threshold)) {
                        $earlyCount++;
                    }
                }
            }

            $effectiveDays = $totalWorkingDays - $leaveDayCount;
            $absentCount   = max(0, $effectiveDays - $presentCount);
            $rate          = $effectiveDays > 0
                ? round($presentCount / $effectiveDays * 100, 1) . '%'
                : 'N/A';

            return [
                $employee->name,
                $employee->email,
                $totalWorkingDays,
                $leaveDayCount,
                $presentCount,
                $absentCount,
                $lateCount,
                $earlyCount,
                $rate,
            ];
        });
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 28,
            'C' => 14,
            'D' => 12,
            'E' => 14,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:I1');

        return [
            1 => [
                'font'      => ['bold' => true, 'italic' => true, 'color' => ['rgb' => '6B7280']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
