<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationAward extends Model
{
    protected $table = 'application_awards';
     public $timestamps = false; 
    protected $fillable = [
        'application_id',
        'award_amount',
        'award_date'
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ApplicationAwardAllocation::class, 'award_id');
    }
}

