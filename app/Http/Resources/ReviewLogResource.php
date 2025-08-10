<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'admin' => $this->whenLoaded('admin', function () {
                return $this->admin ? ['id' => $this->admin->id, 'name' => $this->admin->name] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}
