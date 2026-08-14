<?php

namespace App\Jobs;

use App\Models\AttendanceStaging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAttendanceStaging implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    private int $batchSize = 500;

    /**
     * Process pending staging rows into the attendances table.
     *
     * Rows are claimed atomically (locked + flipped to "processing") so
     * concurrent workers can never process the same row twice. After a batch
     * finishes, the job re-dispatches itself while pending work remains.
     */
    public function handle(): int
    {
        $this->recoverStaleProcessing();

        $ids = DB::transaction(function () {
            $ids = DB::table('attendance_staging')
                ->where('state', AttendanceStaging::STATE_PENDING)
                ->whereNotNull('device_id')
                ->whereNotNull('company_id')
                ->orderBy('id')
                ->limit($this->batchSize)
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::table('attendance_staging')
                    ->whereIn('id', $ids)
                    ->update([
                        'state' => AttendanceStaging::STATE_PROCESSING,
                        'updated_at' => now(),
                    ]);
            }

            return $ids;
        });

        $ids = collect($ids);

        if ($ids->isEmpty()) {
            return 0;
        }

        $rows = DB::table('attendance_staging')->whereIn('id', $ids)->get()->keyBy('id');
        $devices = $this->devicesFor($rows);
        $this->employeeMap($rows);

        foreach ($rows as $row) {
            try {
                $device = $devices->get($row->device_id);

                if (! $device || $device->status !== 'registered') {
                    $this->mark($row->id, AttendanceStaging::STATE_REJECTED, 'Device not registered');

                    continue;
                }

                if (! $row->punch_time) {
                    $this->mark($row->id, AttendanceStaging::STATE_REJECTED, 'Missing punch timestamp');

                    continue;
                }

                if ($this->duplicateExists($row)) {
                    $this->mark($row->id, AttendanceStaging::STATE_DUPLICATE);

                    continue;
                }

                DB::table('attendances')->insert([
                    'company_id' => $row->company_id,
                    'device_id' => $row->device_id,
                    'sn' => $row->sn,
                    'table' => $row->table ?? 'ATTLOG',
                    'stamp' => $row->stamp ?? ' ',
                    'employee_id' => $row->employee_id,
                    'timestamp' => $row->punch_time,
                    'status1' => $row->status1,
                    'status2' => $row->status2,
                    'status3' => $row->status3,
                    'status4' => $row->status4,
                    'status5' => $row->status5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->mark($row->id, AttendanceStaging::STATE_PROCESSED);
            } catch (\Exception $e) {
                Log::error('Attendance staging processing error: '.$e->getMessage(), ['row' => $row->id]);
                $this->mark($row->id, AttendanceStaging::STATE_REJECTED, $e->getMessage());
            }
        }

        if ($this->hasPendingWork()) {
            self::dispatch();
        }

        return $ids->count();
    }

    /**
     * Recover rows stuck in "processing" (e.g. a worker died mid-batch) so
     * they can be picked up again.
     */
    private function recoverStaleProcessing(): void
    {
        DB::table('attendance_staging')
            ->where('state', AttendanceStaging::STATE_PROCESSING)
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update([
                'state' => AttendanceStaging::STATE_PENDING,
                'updated_at' => now(),
            ]);
    }

    private function hasPendingWork(): bool
    {
        return DB::table('attendance_staging')
            ->where('state', AttendanceStaging::STATE_PENDING)
            ->whereNotNull('device_id')
            ->whereNotNull('company_id')
            ->exists();
    }

    /**
     * Load all referenced devices in a single query.
     */
    private function devicesFor(Collection $rows): Collection
    {
        $deviceIds = $rows->pluck('device_id')->filter()->unique();

        if ($deviceIds->isEmpty()) {
            return collect();
        }

        return DB::table('devices')
            ->whereIn('id', $deviceIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * Ensure every referenced employee exists, using batched inserts.
     */
    private function employeeMap(Collection $rows): Collection
    {
        $pairs = $rows
            ->filter(fn ($row) => $row->company_id !== null && $row->employee_id !== null)
            ->map(fn ($row) => [
                'company_id' => (int) $row->company_id,
                'employee_id' => (string) $row->employee_id,
            ])
            ->unique(fn ($pair) => $pair['company_id'].'|'.$pair['employee_id'])
            ->values();

        if ($pairs->isEmpty()) {
            return collect();
        }

        $companyIds = $pairs->pluck('company_id')->all();
        $employeeIds = $pairs->pluck('employee_id')->all();

        $existingKeys = DB::table('employees')
            ->whereIn('company_id', $companyIds)
            ->whereIn('employee_id', $employeeIds)
            ->get(['company_id', 'employee_id'])
            ->map(fn ($e) => (int) $e->company_id.'|'.$e->employee_id)
            ->all();

        $toInsert = $pairs->filter(fn ($pair) => ! in_array($pair['company_id'].'|'.$pair['employee_id'], $existingKeys, true));

        foreach ($toInsert->chunk(200) as $chunk) {
            DB::table('employees')->insert(
                $chunk->map(fn ($pair) => [
                    'company_id' => $pair['company_id'],
                    'employee_id' => $pair['employee_id'],
                    'name' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        }

        return DB::table('employees')
            ->whereIn('company_id', $companyIds)
            ->whereIn('employee_id', $employeeIds)
            ->get(['company_id', 'employee_id'])
            ->keyBy(fn ($e) => (int) $e->company_id.'|'.$e->employee_id);
    }

    private function duplicateExists(object $row): bool
    {
        return DB::table('attendances')
            ->where('company_id', $row->company_id)
            ->where('employee_id', $row->employee_id)
            ->where('status1', $row->status1)
            ->where('sn', $row->sn)
            ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, timestamp, ?)) <= 5', [$row->punch_time])
            ->exists();
    }

    private function mark(int $id, string $state, ?string $error = null): void
    {
        DB::table('attendance_staging')->where('id', $id)->update([
            'state' => $state,
            'error' => $error,
            'updated_at' => now(),
        ]);
    }
}
