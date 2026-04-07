<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'year' => $this->year,
            'month' => $this->month,
            'base_salary' => $this->base_salary,
            'meal_allowance' => $this->meal_allowance,
            'overtime_pay' => $this->overtime_pay,
            'perfect_attendance_bonus' => $this->perfect_attendance_bonus,
            'labor_insurance_employee' => $this->labor_insurance_employee,
            'health_insurance_employee' => $this->health_insurance_employee,
            'net_salary' => $this->net_salary,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
