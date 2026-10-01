<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class EnrollmentApplication extends Model
{
    protected $fillable = [
        'application_number', 'first_name', 'middle_name', 'last_name', 'suffix',
        'birth_date', 'gender', 'civil_status', 'nationality', 'email',
        'contact_number', 'house_block_lot', 'street', 'barangay', 'city', 'province',
        'department_id', 'program_id', 'student_type', 'year_level', 'school_year',
        'student_id', 'semester', 'status', 'rejection_reason', 'sample_username', 'sample_password',
        'submission_token',
        'submitted_at', 'approved_at', 'rejected_at', 'approved_by', 'rejected_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'sample_password' => 'encrypted',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'application_number';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollment_application_subject')
            ->withTimestamps();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'application_id');
    }

    public function emailHistories(): HasMany
    {
        return $this->hasMany(EmailHistory::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])));
    }

    public function getAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->house_block_lot,
            $this->street,
            $this->barangay,
            $this->city,
            $this->province,
        ]));
    }
}
