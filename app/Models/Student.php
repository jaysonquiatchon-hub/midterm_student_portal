<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number', 'first_name', 'last_name', 'email',
        'birth_date', 'year_level', 'program_id', 'user_id', 'student_id',
        'middle_name', 'suffix', 'gender', 'civil_status', 'nationality',
        'contact_number', 'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    // A student belongs to one department
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    // A student is enrolled in many courses (through course_student)
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)
            ->withPivot('grade', 'enrollment_id')
            ->withTimestamps();
    }

    // Accessor: full name helper
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->latest();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(EnrollmentApplication::class);
    }
}
