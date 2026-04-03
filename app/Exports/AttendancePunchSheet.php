<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendancePunchSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(private readonly Collection $punches) {}

    public function title(): string
    {
        return '出勤紀錄';
    }

    public function headings(): array
    {
        return ['員工姓名', '電子郵件', '類型', '打卡時間', '緯度', '經度'];
    }

    public function collection(): Collection
    {
        return $this->punches->map(fn($punch) => [
            $punch->user->name,
            $punch->user->email,
            $punch->type === 'in' ? '上班打卡' : '下班打卡',
            $punch->punch_time->format('Y-m-d H:i:s'),
            $punch->latitude,
            $punch->longitude,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 28,
            'C' => 12,
            'D' => 22,
            'E' => 12,
            'F' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
