<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['department_id', 'code', 'title', 'units', 'year_level', 'semester', 'status', 'program_id'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot('grade', 'enrollment_id')->withTimestamps();
    }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_course')->withTimestamps();
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(EnrollmentApplication::class, 'enrollment_application_subject')
            ->withTimestamps();
    }
}
