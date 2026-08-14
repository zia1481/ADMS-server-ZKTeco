<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Employee extends Model
{
    use BelongsToCompany, HasFactory;

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

    public function scheduleForDate(Carbon $date, ?Collection $companySchedules = null): ?Schedule
    {
        $dateString = $date->toDateString();
        $dayOfWeek = $date->dayOfWeek;

        $assigned = $this->schedules
            ->filter(function (Schedule $schedule) use ($dateString) {
                if (! $schedule->is_active) {
                    return false;
                }

                $effectiveFrom = $schedule->pivot->effective_from ?? null;
                $effectiveTo = $schedule->pivot->effective_to ?? null;

                if ($effectiveFrom && $effectiveFrom > $dateString) {
                    return false;
                }

                if ($effectiveTo && $effectiveTo < $dateString) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (Schedule $schedule) => $schedule->pivot->effective_from ?? '')
            ->first(function (Schedule $schedule) use ($dayOfWeek) {
                return $this->worksOnDay($schedule, $dayOfWeek);
            });

        if ($assigned) {
            return $assigned;
        }

        $candidates = $companySchedules ?? $this->loadCompanySchedules($dateString);

        return $candidates
            ->filter(function (Schedule $schedule) use ($dateString) {
                if (! $schedule->is_active) {
                    return false;
                }

                if ((int) $schedule->company_id !== (int) $this->company_id) {
                    return false;
                }

                if ($schedule->department_id !== $this->department_id && $schedule->department_id !== null) {
                    return false;
                }

                $effectiveFrom = $schedule->effective_from ? $schedule->effective_from->toDateString() : null;
                $effectiveTo = $schedule->effective_to ? $schedule->effective_to->toDateString() : null;

                if ($effectiveFrom && $effectiveFrom > $dateString) {
                    return false;
                }

                if ($effectiveTo && $effectiveTo < $dateString) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (Schedule $schedule) => $schedule->department_id !== null)
            ->sortByDesc(fn (Schedule $schedule) => $schedule->effective_from ? $schedule->effective_from->toDateString() : '')
            ->first(function (Schedule $schedule) use ($dayOfWeek) {
                return $this->worksOnDay($schedule, $dayOfWeek);
            });
    }

    private function loadCompanySchedules(string $dateString): Collection
    {
        return Schedule::forCompany($this->company_id)
            ->where('is_active', true)
            ->with('shift')
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
            ->get();
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
