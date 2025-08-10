<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholarship extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'name',
        'description',
        'application_deadline',
        'award_amount',
        'total_budget',
        'max_awards',
        'status',
        'eligibility_criteria',
        'created_by',
    ];

    public function budgets()
    {
        return $this->hasMany(ScholarshipBudget::class);
    }
    public function disbursementSchedules()
    {
        return $this->hasMany(DisbursementSchedule::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
    

}
