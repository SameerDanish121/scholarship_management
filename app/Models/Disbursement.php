<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disbursement extends Model
{
    protected $table = 'disbursements';
    public $timestamps = false;
    protected $fillable = [
        'award_allocation_id',
        'amount',
        'disbursement_date',
        'reference_number',
        'idempotency_key'
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(ApplicationAwardAllocation::class, 'award_allocation_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
    public function awardAllocation()
    {
        return $this->belongsTo(ApplicationAwardAllocation::class, 'award_allocation_id');
    }

}

