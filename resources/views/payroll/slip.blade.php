<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <title>薪資單 - {{ $record->year }}年{{ $record->month }}月</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; color: #333; font-size: 14px; max-width: 700px; margin: 0 auto; }
        h1 { font-size: 22px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 24px; font-size: 13px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 24px; background: #f9f9f9; padding: 16px; border-radius: 6px; border: 1px solid #e5e5e5; }
        .info-item { display: flex; gap: 8px; align-items: flex-start; }
        .info-label { color: #666; font-size: 12px; min-width: 72px; }
        .info-value { font-weight: 600; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td, th { border: 1px solid #ddd; padding: 9px 14px; text-align: left; font-size: 13px; }
        th { background: #f0f4f8; font-weight: 700; color: #555; }
        .amount { text-align: right; font-family: 'Courier New', monospace; }
        .subtotal td { background: #f5f5f5; font-weight: 600; }
        .net-pay td { background: #e8f4e8; font-weight: 700; font-size: 16px; }
        .net-pay .amount { color: #2d6a2d; }
        .note { margin-top: 16px; color: #666; font-size: 12px; }
        .print-btn { display: block; margin: 28px auto 0; padding: 10px 36px; background: #4a6cf7; color: #fff; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
        .print-btn:hover { background: #3a5ce5; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 20px; }
        }
    </style>
</head>
<body>
    <h1>{{ $record->user->company->name ?? '公司名稱' }}</h1>
    <p class="subtitle">薪 資 單 &nbsp;／&nbsp; {{ $record->year }} 年 {{ $record->month }} 月</p>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">員工姓名</span>
            <span class="info-value">{{ $record->user->name }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">員工編號</span>
            <span class="info-value">{{ $record->user->employee_id ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">職　　稱</span>
            <span class="info-value">{{ $record->user->position ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">薪資狀態</span>
            <span class="info-value">{{ $record->status === 'confirmed' ? '已確認' : '草稿' }}</span>
        </div>
        @if($record->confirmed_at)
        <div class="info-item">
            <span class="info-label">確認日期</span>
            <span class="info-value">{{ $record->confirmed_at->format('Y-m-d') }}</span>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr><th colspan="2">應發項目</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>本薪</td>
                <td class="amount">NT$ {{ number_format((float)$record->base_salary, 0) }}</td>
            </tr>
            <tr>
                <td>伙食津貼</td>
                <td class="amount">NT$ {{ number_format((float)$record->meal_allowance, 0) }}</td>
            </tr>
            <tr>
                <td>全勤獎金</td>
                <td class="amount">NT$ {{ number_format((float)$record->perfect_attendance_bonus, 0) }}</td>
            </tr>
            <tr>
                <td>加班費</td>
                <td class="amount">NT$ {{ number_format((float)$record->overtime_pay, 0) }}</td>
            </tr>
            <tr class="subtotal">
                <td>應發小計</td>
                <td class="amount">NT$ {{ number_format((float)$record->base_salary + (float)$record->meal_allowance + (float)$record->perfect_attendance_bonus + (float)$record->overtime_pay, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="2">扣除項目</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>遲到扣款</td>
                <td class="amount">NT$ {{ number_format((float)$record->late_deduction, 0) }}</td>
            </tr>
            <tr>
                <td>事假扣款</td>
                <td class="amount">NT$ {{ number_format((float)$record->leave_deduction, 0) }}</td>
            </tr>
            <tr>
                <td>勞保（自負額）</td>
                <td class="amount">NT$ {{ number_format((float)$record->labor_insurance, 0) }}</td>
            </tr>
            <tr>
                <td>健保（自負額）</td>
                <td class="amount">NT$ {{ number_format((float)$record->health_insurance, 0) }}</td>
            </tr>
            <tr class="subtotal">
                <td>扣除小計</td>
                <td class="amount">NT$ {{ number_format((float)$record->late_deduction + (float)$record->leave_deduction + (float)$record->labor_insurance + (float)$record->health_insurance, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr class="net-pay">
                <td><strong>實發薪資</strong></td>
                <td class="amount">NT$ {{ number_format((float)$record->net_pay, 0) }}</td>
            </tr>
        </tbody>
    </table>

    @if($record->note)
    <p class="note"><strong>備註：</strong>{{ $record->note }}</p>
    @endif

    <button class="print-btn no-print" onclick="window.print()">🖨 列印薪資單</button>
</body>
</html>
