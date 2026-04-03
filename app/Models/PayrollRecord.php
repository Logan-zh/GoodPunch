<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'company_id', 'year', 'month',
    'base_salary', 'meal_allowance', 'perfect_attendance_bonus',
    'overtime_pay', 'late_deduction', 'leave_deduction',
    'labor_insurance', 'health_insurance', 'gross_pay', 'net_pay',
    'status', 'confirmed_at', 'note',
])]
class PayrollRecord extends Model
{
    use HasCompany;

    protected function casts(): array
    {
        return [
            'base_salary'              => 'decimal:2',
            'meal_allowance'           => 'decimal:2',
            'perfect_attendance_bonus' => 'decimal:2',
            'overtime_pay'             => 'decimal:2',
            'late_deduction'           => 'decimal:2',
            'leave_deduction'          => 'decimal:2',
            'labor_insurance'          => 'decimal:2',
            'health_insurance'         => 'decimal:2',
            'gross_pay'                => 'decimal:2',
            'net_pay'                  => 'decimal:2',
            'confirmed_at'             => 'datetime',
            'year'                     => 'integer',
            'month'                    => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
