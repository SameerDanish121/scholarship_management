<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
     protected $table = 'documents';
    public $timestamps = false;
    protected $fillable = [
        'application_id', 'filename', 'file_path', 'uploaded_at'
    ];


    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
