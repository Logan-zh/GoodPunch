<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PunchCorrectionResource extends JsonResource
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
            'correction_date' => $this->correction_date,
            'correction_type' => $this->correction_type,
            'requested_time' => $this->requested_time,
            'reason' => $this->reason,
            'status' => $this->status,
            'approval_note' => $this->approval_note,
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
