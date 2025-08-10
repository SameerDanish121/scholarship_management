<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReceiptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'url' => asset(Storage::url($this->file_path)),
            'amount' => $this->amount,
            'uploaded_at' => $this->uploaded_at,
        ];
    }
}