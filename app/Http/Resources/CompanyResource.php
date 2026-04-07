<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'tax_id' => $this->tax_id,
            'work_hours_per_day' => $this->work_hours_per_day,
            'work_start_time' => $this->work_start_time,
            'work_end_time' => $this->work_end_time,
            'leave_approval_chain' => $this->leave_approval_chain,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
