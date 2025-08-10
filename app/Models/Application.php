<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{

    protected $table = 'applications';
    public $timestamps = false;
    protected $fillable = [
        'scholarship_id',
        'student_id',
        'status',
        'submitted_at'
    ];


    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }
    public function award(): HasOne
    {
        return $this->hasOne(ApplicationAward::class);
    }
    public function awards()
    {
        return $this->hasMany(ApplicationAward::class);
    }

}
