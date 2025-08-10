<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $table = 'receipts';
       public $timestamps = false; 
    protected $fillable = [
        'disbursement_id', 'filename', 'file_path', 'amount', 'uploaded_at'
    ];

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }
}
