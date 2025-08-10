<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_id',
        'cost_category_id',
        'scheduled_date',
        'scheduled_amount',
        'description',
        'status',
        'award_allocation_id'
    ];

    public $timestamps = false;

    public function scholarship()
    {
        return $this->belongsTo(Scholarship::class);
    }
    public function costCategory()
    {
        return $this->belongsTo(CostCategory::class);
    }
    public function awardAllocation()
    {
        return $this->belongsTo(ApplicationAwardAllocation::class, 'award_allocation_id');
    }
    

}
