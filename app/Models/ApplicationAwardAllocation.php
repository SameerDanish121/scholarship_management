<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationAwardAllocation extends Model
{
    protected $table = 'application_award_allocations';
    public $timestamps = false;
    protected $fillable = [
        'award_id',
        'cost_category_id',
        'allocated_amount'
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(ApplicationAward::class, 'award_id');
    }

    public function costCategory(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class, 'award_allocation_id');
    }
    public function applicationAward()
    {
        return $this->belongsTo(ApplicationAward::class, 'application_award_id');
    }

}

