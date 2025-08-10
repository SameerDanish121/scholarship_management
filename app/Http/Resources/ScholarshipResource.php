<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'application_deadline' => $this->application_deadline,
            'applications_count' => $this->whenCounted('applications'),
        ];
    }
}
