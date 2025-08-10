<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLog extends Model
{
    protected $table = 'review_logs';
    public $timestamps = false;
    protected $fillable = [
        'application_id',
        'admin_id',
        'action'
    ];




    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
