<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApprovalStep extends Model
{
    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'step_index',
        'step_name',
        'status',
        'comment',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
