<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'shift_id',
        'department_id',
        'name',
        'working_days',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'working_days' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_schedule')
            ->withPivot(['company_id', 'effective_from', 'effective_to'])
            ->withTimestamps();
    }
}
