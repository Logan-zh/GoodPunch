<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->role,
            'employee_id' => $this->employee_id,
            'position' => $this->position,
            'hired_at' => $this->hired_at?->toDateString(),
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'supervisor_id' => $this->supervisor_id,
            'supervisor' => $this->whenLoaded('supervisor', fn () => [
                'id' => $this->supervisor->id,
                'name' => $this->supervisor->name,
            ]),
            'leave_entitlements' => $this->when(
                isset($this->resource->leave_entitlements),
                $this->resource->leave_entitlements ?? null,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
