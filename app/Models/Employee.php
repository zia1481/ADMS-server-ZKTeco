<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'department_id',
        'employee_id',
        'name',
        'email',
        'phone',
        'position',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'employee_schedule')
            ->withPivot(['company_id', 'effective_from', 'effective_to'])
            ->withTimestamps();
    }

    public function scheduleForDate(Carbon $date): ?Schedule
    {
        $dateString = $date->toDateString();
        $dayOfWeek = $date->dayOfWeek;

        $assigned = $this->schedules()
            ->where(function ($q) use ($dateString) {
                $q->whereNull('employee_schedule.effective_from')
                    ->orWhere('employee_schedule.effective_from', '<=', $dateString);
            })
            ->where(function ($q) use ($dateString) {
                $q->whereNull('employee_schedule.effective_to')
                    ->orWhere('employee_schedule.effective_to', '>=', $dateString);
            })
            ->where('schedules.is_active', true)
            ->orderByDesc('employee_schedule.effective_from')
            ->get()
            ->first(function ($schedule) use ($dayOfWeek) {
                return $this->worksOnDay($schedule, $dayOfWeek);
            });

        if ($assigned) {
            return $assigned;
        }

        return Schedule::forCompany($this->company_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('department_id', $this->department_id)
                    ->orWhereNull('department_id');
            })
            ->where(function ($q) use ($dateString) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $dateString);
            })
            ->where(function ($q) use ($dateString) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $dateString);
            })
            ->orderByRaw('department_id IS NOT NULL DESC')
            ->orderByDesc('effective_from')
            ->get()
            ->first(function ($schedule) use ($dayOfWeek) {
                return $this->worksOnDay($schedule, $dayOfWeek);
            });
    }

    private function worksOnDay(Schedule $schedule, int $dayOfWeek): bool
    {
        $days = $schedule->working_days;

        if ($days === null || $days === []) {
            return true;
        }

        return in_array($dayOfWeek, array_map('intval', $days), true);
    }
}
