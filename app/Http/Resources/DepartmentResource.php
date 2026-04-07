<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'company_id' => $this->company_id,
            'parent_id' => $this->parent_id,
            'manager_id' => $this->manager_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
