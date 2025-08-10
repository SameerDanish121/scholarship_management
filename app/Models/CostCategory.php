<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];
   public $timestamps = false;

    public function scholarshipBudgets()
    {
        return $this->hasMany(ScholarshipBudget::class);
    }


    public function disbursementSchedules()
    {
        return $this->hasMany(DisbursementSchedule::class);
    }
}
