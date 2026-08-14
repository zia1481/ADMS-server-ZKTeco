<?php

namespace App\Http\Controllers;

use App\Models\FingerLog;
use App\Models\PendingDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class iclockController extends Controller
{
    public function __invoke(Request $request) {}

    // handshake
    public function handshake(Request $request)
    {
        $sn = $request->input('SN') ?? ' ';

        $this->logHandshake($request);

        $this->debugLog('HANDSHAKE', [
            'sn' => $sn,
            'request' => $request->all(),
            'ip' => $request->ip(),
            'body' => $request->getContent(),
        ]);

        $device = DB::table('devices')->where('no_sn', $sn)->first();

        if (! $this->commKeyAccepted($request, $device)) {
            $this->debugLog('HANDSHAKE REJECTED (comm key)', ['sn' => $sn]);

            return 'ERROR: 0';
        }

        if ($device) {
            if ($device->status === 'blocked') {
                $this->debugLog('HANDSHAKE REJECTED (blocked)', ['sn' => $sn]);

                return 'ERROR: 0';
            }

            DB::table('devices')->where('no_sn', $sn)->update(['online' => now()]);

            if (! $device->company_id) {
                $this->detectNewDevice($request);
            }
        } else {
            $this->detectNewDevice($request);
        }

        $response = $this->buildHandshakeResponse($sn, $request->input('DeviceType'), $device);

        $this->debugLog('HANDSHAKE RESPONSE', [
            'sn' => $sn,
            'response' => $response,
        ]);

        return $response;
    }

    public function receiveRecords(Request $request)
    {
        $sn = $request->input('SN') ?? ' ';
        $table = strtoupper($request->input('table') ?? 'ATTLOG');
        $device = DB::table('devices')->where('no_sn', $sn)->first();

        $this->debugLog('PUNCH DATA RECEIVED', [
            'sn' => $sn,
            'table' => $table,
            'stamp' => $request->input('Stamp'),
            'ip' => $request->ip(),
            'presented_key' => $this->presentedCommKey($request),
            'body' => $request->getContent(),
        ]);

        if (! $device) {
            $this->detectNewDevice($request);

            $this->debugLog('PUNCH REJECTED (unknown device)', ['sn' => $sn]);

            return 'ERROR: 0';
        }

        if ($device->status === 'blocked') {
            $this->debugLog('PUNCH REJECTED (blocked)', ['sn' => $sn]);

            return 'ERROR: 0';
        }

        if (! $device->company_id) {
            $this->detectNewDevice($request);
        }

        if ($table === 'OPTIONS') {
            // ZKTeco devices push their options/capabilities right after the
            // handshake WITHOUT the ComKey. Acknowledge it and move on; the
            // device only carries the key on real data pushes (ATTLOG, etc).
            $this->debugLog('DEVICE OPTIONS PUSH', [
                'sn' => $sn,
                'body' => $request->getContent(),
            ]);

            return 'OK: 0';
        }

        if ($device->comm_key_enforce && ! $this->commKeyAccepted($request, $device, true)) {
            $this->debugLog('PUNCH REJECTED (comm key)', ['sn' => $sn]);

            return 'ERROR: 0';
        }

        if ($table !== 'ATTLOG') {
            // Non-attendance tables (OPERLOG, OPTLOG, USER, FP, FACE, ...) are
            // acknowledged so the device clears its buffer, but not parsed.
            $this->debugLog('NON-ATTLOG DATA PUSH ACKED', [
                'sn' => $sn,
                'table' => $table,
            ]);

            return 'OK: 0';
        }

        $maxLength = 6550;
        $body = $request->getContent();
        if (strlen($body) > $maxLength) {
            $body = substr($body, 0, $maxLength);
        }
        // Log the incoming request
        if ($this->logPunches()) {
            FingerLog::create([
                'url' => json_encode($request->all()),
                'data' => $body,
            ]);
        }

        try {
            $arr = preg_split('/\\r\\n|\\r|\\n/', $request->getContent());
            $tot = 0;
            $staging = [];

            foreach ($arr as $record) {
                if (empty($record)) {
                    continue;
                }

                $data = explode("\t", $record);
                if (! empty($data) && isset($data[0]) && is_numeric($data[0])) {
                    $this->debugLog('PUNCH', [
                        'sn' => $sn,
                        'record' => $record,
                        'fields' => $data,
                    ]);
                    $staging[] = [
                        'sn' => $sn,
                        'device_id' => $device?->id,
                        'company_id' => $device?->company_id,
                        'table' => $request->input('table') ?? 'ATTLOG',
                        'stamp' => $request->input('Stamp') ?? ' ',
                        'payload' => $record,
                        'employee_id' => $data[0],
                        'punch_time' => $data[1] ?? null,
                        'status1' => $this->validateAndFormatInteger($data[2] ?? null),
                        'status2' => $this->validateAndFormatInteger($data[3] ?? null),
                        'status3' => $this->validateAndFormatInteger($data[4] ?? null),
                        'status4' => $this->validateAndFormatInteger($data[5] ?? null),
                        'status5' => $this->validateAndFormatInteger($data[6] ?? null),
                        'state' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $tot++;
                } else {
                    Log::info('Invalid or incomplete data: '.$record);
                }
            }

            // Perform batch insert into staging table
            if (! empty($staging)) {
                DB::table('attendance_staging')->insert($staging);

                // Advance the device's stored stamp to the newest record received so
                // future handshakes only request incremental data (Stamp=0 -> upload all).
                $maxStamp = null;
                foreach ($staging as $row) {
                    $ts = $row['punch_time'] ? strtotime($row['punch_time']) : false;
                    if ($ts !== false && ($maxStamp === null || $ts > $maxStamp)) {
                        $maxStamp = $ts;
                    }
                }
                if ($maxStamp !== null) {
                    DB::table('devices')->where('no_sn', $sn)->update(['attlog_stamp' => $maxStamp]);
                }

                \App\Jobs\ProcessAttendanceStaging::dispatch();
            }

            $this->debugLog('PUNCH ACCEPTED', [
                'sn' => $sn,
                'count' => $tot,
            ]);

            return 'OK: '.$tot; // Success response
        } catch (\Exception $e) {
            $this->debugLog('PUNCH ERROR', [
                'sn' => $sn,
                'error' => $e->getMessage(),
            ]);
            $errorData = [
                'data' => $e->getMessage().'::Line::'.$e->getLine(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('error_log')->insert($errorData);
            Log::error($e);

            return "ERROR: 0\n";
        }
    }

    public function test(Request $request)
    {
        $sn = $request->input('SN');
        $device = $sn ? DB::table('devices')->where('no_sn', $sn)->first() : null;

        if (! $this->commKeyAccepted($request, $device)) {
            return 'ERROR: 0';
        }

        if ($this->logPunches()) {
            DB::table('finger_log')->insert([
                'data' => $request->getContent(),
                'url' => $request->fullUrl(),
            ]);
        }

        return 'OK';
    }

    public function getrequest(Request $request)
    {
        $sn = $request->input('SN');

        $device = DB::table('devices')->where('no_sn', $sn)->first();

        if (! $this->commKeyAccepted($request, $device)) {
            return 'ERROR: 0';
        }

        if ($device) {
            DB::table('devices')->where('no_sn', $sn)->update(['online' => now()]);

            if (! $device->company_id) {
                $this->detectNewDevice($request);
            }
        } else {
            $this->detectNewDevice($request);
        }

        return 'OK';
    }

    /**
     * Persist (or update) an unknown device into the pending_devices table so
     * an administrator can assign it to a company and area later.
     */
    private function detectNewDevice(Request $request): void
    {
        $sn = $request->input('SN');

        if (! $sn) {
            return;
        }

        try {
            $existing = DB::table('pending_devices')->where('sn', $sn)->first();

            if ($existing) {
                DB::table('pending_devices')
                    ->where('sn', $sn)
                    ->update([
                        'last_seen' => now(),
                        'ip_address' => $existing->ip_address ?: $request->ip(),
                        'model' => $existing->model ?: $request->input('DeviceType'),
                        'fw_ver' => $existing->fw_ver ?: $request->input('FWVersion'),
                        'push_ver' => $existing->push_ver ?: $request->input('pushver'),
                        'options' => $existing->options ?: json_encode($request->all()),
                        'state' => $existing->state ?: PendingDevice::STATE_DETECTED,
                    ]);
            } else {
                DB::table('pending_devices')->insert([
                    'sn' => $sn,
                    'ip_address' => $request->ip(),
                    'model' => $request->input('DeviceType'),
                    'fw_ver' => $request->input('FWVersion'),
                    'push_ver' => $request->input('pushver'),
                    'options' => json_encode($request->all()),
                    'first_seen' => now(),
                    'last_seen' => now(),
                    'state' => PendingDevice::STATE_DETECTED,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to detect new device: '.$e->getMessage());
        }
    }

    private function debugLog(string $message, array $context = []): void
    {
        if (! filter_var(env('ICLOCK_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        Log::channel('iclock')->debug($message, $context);
    }

    private function logHandshakes(): bool
    {
        return filter_var(env('ICLOCK_LOG_HANDSHAKES', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function logPunches(): bool
    {
        return filter_var(env('ICLOCK_LOG_PUNCHES', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function logHandshake(Request $request): void
    {
        if (! $this->logHandshakes()) {
            return;
        }

        $data = [
            'url' => json_encode($request->all()),
            'data' => $request->getContent(),
            'sn' => $request->input('SN'),
            'option' => $request->input('option'),
        ];
        DB::table('device_log')->insert($data);
    }

    private function buildHandshakeResponse(string $sn, ?string $deviceType = null, ?object $device = null): string
    {
        $config = DB::table('device_handshake_configs')
            ->where(function ($query) use ($deviceType) {
                if ($deviceType) {
                    $query->where('device_type', $deviceType);
                }
                $query->orWhere('device_type', 'default');
            })
            ->orderByRaw('CASE WHEN device_type = ? THEN 0 ELSE 1 END', [$deviceType])
            ->first();

        $stamp = ($device && $device->attlog_stamp !== null) ? (int) $device->attlog_stamp : 0;
        $errorDelay = $config->error_delay ?? 60;
        $delay = $config->delay ?? 30;
        $resLogDay = $config->res_log_day ?? 18250;
        $resLogDelCount = $config->res_log_del_count ?? 10000;
        $resLogCount = $config->res_log_count ?? 50000;
        $transTimes = $config->trans_times ?? '00:00;14:05';
        $transInterval = $config->trans_interval ?? 1;
        $transFlag = $config->trans_flag ?? '1111000000';
        $timeZone = $config->time_zone ?? null;
        $realtime = $config->realtime ?? true;
        $encrypt = $config->encrypt ?? false;

        $r = "GET OPTION FROM: {$sn}\r\n".
            "Stamp={$stamp}\r\n".
            'OpStamp='.time()."\r\n".
            "ErrorDelay={$errorDelay}\r\n".
            "Delay={$delay}\r\n".
            "ResLogDay={$resLogDay}\r\n".
            "ResLogDelCount={$resLogDelCount}\r\n".
            "ResLogCount={$resLogCount}\r\n".
            "TransTimes={$transTimes}\r\n".
            "TransInterval={$transInterval}\r\n".
            "TransFlag={$transFlag}\r\n";

        if ($timeZone) {
            $r .= "TimeZone={$timeZone}\r\n";
        }

        $r .= 'Realtime='.($realtime ? 1 : 0)."\r\n".
            'Encrypt='.($encrypt ? 1 : 0);

        if ($device && ! empty($device->comm_key)) {
            $r .= "\r\nIsPushComKey=1\r\nPushComKey=".$device->comm_key;
        }

        return $r;
    }

    /**
     * Extract the communication key presented by the device from the query
     * string (ZKTeco ComKey), an HTTP header, or HTTP Basic Auth credentials.
     */
    private function presentedCommKey(Request $request): ?string
    {
        $key = $request->query('ComKey');

        if ($key === null || $key === '') {
            $key = $request->query('commkey');
        }

        if (($key === null || $key === '') && $request->hasHeader('X-Comm-Key')) {
            $key = $request->header('X-Comm-Key');
        }

        if (($key === null || $key === '') && $request->hasHeader('CommKey')) {
            $key = $request->header('CommKey');
        }

        if (($key === null || $key === '') && $request->getUser() !== null) {
            $key = $request->getUser();
        }

        if (($key === null || $key === '') && $request->getPassword() !== null) {
            $key = $request->getPassword();
        }

        return $key !== null ? trim((string) $key) : null;
    }

    /**
     * Validate the device's communication key.
     *
     * Unknown devices and registered devices without a stored key pass in
     * non-strict mode (handshake/heartbeat only). In strict mode (data push)
     * a device must have a stored key that matches the presented key.
     *
     * A device with a stored key that presents no key is still allowed to
     * handshake (non-strict) so it can receive PushComKey + Stamp from the
     * handshake response; the key remains strictly enforced on data pushes.
     */
    private function commKeyAccepted(Request $request, ?object $device, bool $strict = false): bool
    {
        if (! $device) {
            return ! $strict;
        }

        $stored = $device->comm_key ?? null;

        if ($stored === null || $stored === '') {
            return true;
        }

        $presented = $this->presentedCommKey($request);

        if ($strict) {
            return $presented !== null && hash_equals((string) $stored, $presented);
        }

        return $presented === null || hash_equals((string) $stored, $presented);
    }

    private function validateAndFormatInteger($value)
    {
        return isset($value) && $value !== '' ? (int) $value : null;
    }
}
