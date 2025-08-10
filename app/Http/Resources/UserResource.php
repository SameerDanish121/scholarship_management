<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenLoaded('role', function () {
                return $this->role->name;
            }),
            'student' => $this->when(
                $this->relationLoaded('role') && 
                $this->role->name === 'student' && 
                $this->relationLoaded('student'),
                $this->student
            ),
            'admin' => $this->when(
                $this->relationLoaded('role') && 
                $this->role->name === 'admin' && 
                $this->relationLoaded('admin'),
                $this->admin
            ),
        ];
    }
}
