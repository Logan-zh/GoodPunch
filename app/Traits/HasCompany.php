<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasCompany
{
    protected static function bootHasCompany()
    {
        static::creating(function ($model) {
            if (Auth::check() && ! $model->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            // Use hasUser() to check if user is already resolved without triggering a query
            if (Auth::hasUser()) {
                $user = Auth::user();
                
                // Super Admin can see everything
                if ($user->role === 'admin') {
                    return;
                }
                
                $builder->where('company_id', $user->company_id);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
