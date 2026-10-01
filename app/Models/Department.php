<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    // One department has many students
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}