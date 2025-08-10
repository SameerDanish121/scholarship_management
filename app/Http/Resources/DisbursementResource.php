<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisbursementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'date' => $this->disbursement_date,
            'reference_number' => $this->reference_number,
            'receipts' => ReceiptResource::collection($this->whenLoaded('receipts')),
        ];
    }
}