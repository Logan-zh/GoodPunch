<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'company_id', 'base_salary', 'meal_allowance', 'perfect_attendance_bonus',
    'labor_insurance_employee', 'health_insurance_employee', 'overtime_hourly_rate', 'effective_from',
])]
class SalaryStructure extends Model
{
    use HasCompany;

    protected function casts(): array
    {
        return [
            'base_salary'               => 'decimal:2',
            'meal_allowance'            => 'decimal:2',
            'perfect_attendance_bonus'  => 'decimal:2',
            'labor_insurance_employee'  => 'decimal:2',
            'health_insurance_employee' => 'decimal:2',
            'overtime_hourly_rate'      => 'decimal:2',
            'effective_from'            => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
