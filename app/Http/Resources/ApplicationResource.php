<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'scholarship' => new ScholarshipResource($this->whenLoaded('scholarship')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'review_logs' => ReviewLogResource::collection($this->whenLoaded('reviewLogs')),
            'award' => new AwardResource($this->whenLoaded('award')),
        ];
    }
}