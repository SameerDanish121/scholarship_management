<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AllocationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'allocated_amount' => $this->allocated_amount,
            'cost_category' => $this->whenLoaded('costCategory', function () {
                return $this->costCategory ? ['id' => $this->costCategory->id, 'name' => $this->costCategory->name] : null;
            }),
            'disbursements' => DisbursementResource::collection($this->whenLoaded('disbursements')),
        ];
    }
}