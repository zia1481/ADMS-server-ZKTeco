<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Schedule;
use Carbon\Carbon;

class AttendanceStatusService
{
    public const NO_SCHEDULE = 'no schedule';
    public const ABSENT = 'absent';
    public const ON_TIME = 'on time';
    public const LATE = 'late';
    public const EARLY_LEAVE = 'early leave';
    public const LATE_EARLY_LEAVE = 'late & early leave';

    /**
     * Resolve the schedule that applies to an employee on a given date.
     * Prefers an explicit employee assignment, then falls back to the
     * department-level (or company-wide) schedule.
     */
    public function resolveSchedule(Employee $employee, Carbon $date): ?Schedule
    {
        return $employee->scheduleForDate($date);
    }

    /**
     * Compute scheduled in/out datetimes for a schedule on a given date.
     *
     * @return array{start: Carbon|null, end: Carbon|null}
     */
    public function scheduledTimes(Schedule $schedule, Carbon $date): array
    {
        $shift = $schedule->shift;

        if (!$shift) {
            return ['start' => null, 'end' => null];
        }

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $date->toDateString() . ' ' . $shift->start_time);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $date->toDateString() . ' ' . $shift->end_time);

        if ($end <= $start) {
            $end->addDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Determine the attendance status for an employee on a date.
     *
     * @return array{status: string, schedule: Schedule|null, scheduled_in: Carbon|null, scheduled_out: Carbon|null}
     */
    public function statusFor(Employee $employee, Carbon $date, ?Carbon $firstIn, ?Carbon $lastOut): array
    {
        $schedule = $this->resolveSchedule($employee, $date);

        if (!$schedule) {
            return [
                'status' => self::NO_SCHEDULE,
                'schedule' => null,
                'scheduled_in' => null,
                'scheduled_out' => null,
            ];
        }

        $times = $this->scheduledTimes($schedule, $date);
        $scheduledIn = $times['start'];
        $scheduledOut = $times['end'];
        $shift = $schedule->shift;

        if (!$firstIn) {
            return [
                'status' => self::ABSENT,
                'schedule' => $schedule,
                'scheduled_in' => $scheduledIn,
                'scheduled_out' => $scheduledOut,
            ];
        }

        $late = $scheduledIn !== null
            && $firstIn->gt($scheduledIn->copy()->addMinutes($shift->grace_late_minutes ?? 0));

        $early = $scheduledOut !== null
            && $lastOut !== null
            && $lastOut->lt($scheduledOut->copy()->subMinutes($shift->grace_early_leave_minutes ?? 0));

        if ($late && $early) {
            $status = self::LATE_EARLY_LEAVE;
        } elseif ($late) {
            $status = self::LATE;
        } elseif ($early) {
            $status = self::EARLY_LEAVE;
        } else {
            $status = self::ON_TIME;
        }

        return [
            'status' => $status,
            'schedule' => $schedule,
            'scheduled_in' => $scheduledIn,
            'scheduled_out' => $scheduledOut,
        ];
    }
}
