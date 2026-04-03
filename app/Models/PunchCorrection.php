<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class PunchCorrection extends Model
{
    use HasCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'correction_date',
        'correction_type',
        'requested_time',
        'reason',
        'approval_note',
        'status',
        'approver_id',
        'approved_at',
        'original_punch_id',
    ];

    protected function casts(): array
    {
        return [
            'correction_date' => 'date',
            'requested_time'  => 'datetime',
            'approved_at'     => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function originalPunch()
    {
        return $this->belongsTo(Punch::class, 'original_punch_id');
    }
}
