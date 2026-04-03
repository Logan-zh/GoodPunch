<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Traits\HasCompany;
use App\Models\PunchCorrection;

#[Fillable(['name', 'email', 'password', 'role', 'company_id', 'hired_at', 'supervisor_id', 'employee_id', 'position', 'department_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Remove automatic appends to prevent infinite recursion during auth resolution
    // protected $appends = ['leave_entitlements'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hired_at' => 'date',
        ];
    }

    /**
     * Calculate leave entitlements based on company policy.
     */
    public function getLeaveEntitlementsAttribute()
    {
        if (!$this->hired_at) {
            return [
                'annual' => 0, 'personal' => 0, 'sick' => 0,
                'annual_remaining' => 0, 'personal_remaining' => 0, 'sick_remaining' => 0,
                'years' => 0,
            ];
        }

        $hiredAt = \Carbon\Carbon::parse($this->hired_at);
        $years = $hiredAt->diffInDays(now()) / 365.25;
        $company = $this->company;
        $workHours = $company ? (float)$company->work_hours_per_day : 8.0;
        
        $policies = LeavePolicy::where('company_id', $this->company_id)->get();

        // Total Entitlements in Hours
        $annualTotal = ($policies->where('type', 'annual')->where('min_years', '<=', $years)->sortByDesc('min_years')->first()?->days ?? 0) * $workHours;
        $personalTotal = ($policies->where('type', 'personal')->first()?->days ?? 0) * $workHours;
        $sickTotal = ($policies->where('type', 'sick')->first()?->days ?? 0) * $workHours;

        // Subtract Approved Leave Hours
        $approvedRequests = LeaveRequest::where('user_id', $this->id)
            ->where('status', 'approved')
            ->get();

        $annualUsed = $approvedRequests->where('type', 'annual')->sum('hours');
        $personalUsed = $approvedRequests->where('type', 'personal')->sum('hours');
        $sickUsed = $approvedRequests->where('type', 'sick')->sum('hours');

        return [
            'annual' => round($annualTotal / $workHours, 2),
            'personal' => round($personalTotal / $workHours, 2),
            'sick' => round($sickTotal / $workHours, 2),
            'annual_remaining' => round(($annualTotal - $annualUsed) / $workHours, 2),
            'personal_remaining' => round(($personalTotal - $personalUsed) / $workHours, 2),
            'sick_remaining' => round(($sickTotal - $sickUsed) / $workHours, 2),
            'annual_remaining_hours' => $annualTotal - $annualUsed,
            'personal_remaining_hours' => $personalTotal - $personalUsed,
            'sick_remaining_hours' => $sickTotal - $sickUsed,
            'years' => round($years, 1),
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Get the punches for the user.
     */
    public function punches()
    {
        return $this->hasMany(Punch::class);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function punchCorrections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PunchCorrection::class);
    }

    public function salaryStructure(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SalaryStructure::class);
    }
}
