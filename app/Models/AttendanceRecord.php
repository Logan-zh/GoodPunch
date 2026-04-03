<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'clock_in',
        'clock_out',
        'overtime_in',
        'overtime_out',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_hours',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date'                => 'date',
            'clock_in'            => 'datetime',
            'clock_out'           => 'datetime',
            'overtime_in'         => 'datetime',
            'overtime_out'        => 'datetime',
            'late_minutes'        => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_hours'      => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
