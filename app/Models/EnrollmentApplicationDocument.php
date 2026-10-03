<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentApplicationDocument extends Model
{
    protected $fillable = [
        'enrollment_application_id',
        'requirement_key',
        'label',
        'file_path',
        'original_name',
        'mime_type',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }
}
