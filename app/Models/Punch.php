<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCompany;

class Punch extends Model
{
    use HasCompany;

    protected $fillable = ['user_id', 'type', 'punch_time', 'latitude', 'longitude', 'company_id', 'correction_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'punch_time' => 'datetime',
        ];
    }
}
