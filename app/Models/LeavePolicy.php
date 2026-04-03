<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCompany;

class LeavePolicy extends Model
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'type',
        'min_years',
        'days',
    ];
}
