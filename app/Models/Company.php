<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'tax_id',
        'principal',
        'phone',
        'address',
        'status',
        'work_hours_per_day',
        'work_start_time',
        'work_end_time',
        'leave_approval_chain',
    ];

    protected $casts = [
        'leave_approval_chain' => 'array',
        'work_hours_per_day' => 'decimal:1',
        'status' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function punches()
    {
        return $this->hasMany(Punch::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function leavePolicies()
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
