<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\AttendanceStaging;
use Illuminate\Support\Facades\Log;

class ProcessAttendanceStaging implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * Process pending staging rows into the attendances table.
     */
    public function handle(): int
    {
        $batchSize = 500;

        $rows = DB::table('attendance_staging')
            ->where('state', AttendanceStaging::STATE_PENDING)
            ->whereNotNull('device_id')
            ->whereNotNull('company_id')
            ->limit($batchSize)
            ->get();

        foreach ($rows as $row) {
            try {
                $device = DB::table('devices')->find($row->device_id);

                if (!$device || $device->status !== 'registered') {
                    $this->mark($row->id, AttendanceStaging::STATE_REJECTED, 'Device not registered');
                    continue;
                }

                if (!$row->punch_time) {
                    $this->mark($row->id, AttendanceStaging::STATE_REJECTED, 'Missing punch timestamp');
                    continue;
                }

                $existing = DB::table('attendances')
                    ->where('company_id', $row->company_id)
                    ->where('employee_id', $row->employee_id)
                    ->where('status1', $row->status1)
                    ->where('sn', $row->sn)
                    ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, timestamp, ?)) <= 5', [$row->punch_time])
                    ->exists();

                if ($existing) {
                    $this->mark($row->id, AttendanceStaging::STATE_DUPLICATE);
                    continue;
                }

                Employee::firstOrCreate(
                    ['company_id' => $row->company_id, 'employee_id' => $row->employee_id],
                    ['name' => '']
                );

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
                Log::error('Attendance staging processing error: ' . $e->getMessage(), ['row' => $row->id]);
                $this->mark($row->id, AttendanceStaging::STATE_REJECTED, $e->getMessage());
            }
        }

        return $rows->count();
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
