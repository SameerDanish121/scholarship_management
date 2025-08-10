<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AwardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'award_amount' => $this->award_amount,
            'award_date' => $this->award_date,
            'application_id' => $this->application_id,
            'allocations' => AllocationResource::collection($this->whenLoaded('allocations')),
        ];
    }
}