<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCompany;

class LeaveRequest extends Model
{
    use HasCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'type',
        'start_at',
        'end_at',
        'hours',
        'reason',
        'status',
        'current_step',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function steps()
    {
        return $this->hasMany(LeaveApprovalStep::class);
    }
}
