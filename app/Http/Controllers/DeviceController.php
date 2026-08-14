<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Company;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PendingDevice;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{
    const MAJOR = 1;

    const MINOR = 2;

    const PATCH = 3;

    public static function get()
    {
        $commitHash = trim(exec('git log --pretty="%h" -n1 HEAD'));

        $commitDate = new \DateTime(trim(exec('git log -n1 --pretty=%ci HEAD')));
        $commitDate->setTimezone(new \DateTimeZone('UTC'));

        return sprintf('v%s.%s.%s-dev.%s (%s)', self::MAJOR, self::MINOR, self::PATCH, $commitHash, $commitDate->format('Y-m-d H:i:s'));
    }

    public function index(Request $request)
    {
        $companyId = current_company_id();

        $query = DB::table('devices')
            ->leftJoin('companies', 'devices.company_id', '=', 'companies.id')
            ->leftJoin('areas', 'devices.area_id', '=', 'areas.id')
            ->select(
                'devices.id',
                'devices.nama',
                'devices.no_sn',
                'devices.ip_address',
                'devices.model',
                'devices.status',
                'devices.online',
                'devices.company_id',
                'devices.area_id',
                'devices.comm_key_enforce',
                'companies.name as company_name',
                'areas.name as area_name'
            )
            ->orderBy('devices.online', 'DESC');

        if ($companyId) {
            $query->where('devices.company_id', $companyId);
        }

        if ($request->filled('status')) {
            $query->where('devices.status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('devices.no_sn', 'like', '%'.$request->search.'%')
                    ->orWhere('devices.nama', 'like', '%'.$request->search.'%');
            });
        }

        $data['lable'] = 'Devices';
        $data['log'] = $query->paginate(10)->withQueryString();
        $data['areas'] = Area::forCompany($companyId)->orderBy('name')->get();
        $data['companies'] = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('devices.index', $data);
    }

    public function update(Request $request, Device $device)
    {
        $this->authorizeCompany($device->company_id);

        $request->validate([
            'nama' => 'nullable|string|max:255',
            'area_id' => 'nullable|exists:areas,id',
            'status' => 'nullable|in:registered,pending,blocked',
            'comm_key' => 'nullable|digits_between:4,8',
            'comm_key_enforce' => 'nullable|boolean',
        ]);

        $data = $request->only(['nama', 'area_id', 'status']);

        if ($request->filled('comm_key')) {
            $data['comm_key'] = $request->comm_key;
        }

        $data['comm_key_enforce'] = $request->boolean('comm_key_enforce');

        $device->update($data);

        return redirect()->route('devices.index')->with('success', 'Device updated successfully');
    }

    public function pending(Request $request)
    {
        $query = DB::table('pending_devices')
            ->leftJoin('devices', 'devices.no_sn', '=', 'pending_devices.sn')
            ->select(
                'pending_devices.*',
                'devices.company_id as device_company_id',
                'devices.area_id as device_area_id',
                'devices.comm_key as device_comm_key'
            );

        if ($request->filled('state')) {
            $query->where('pending_devices.state', $request->state);
        }

        $data['lable'] = 'Pending Devices';
        $data['log'] = $query->orderBy('pending_devices.last_seen', 'DESC')->paginate(10)->withQueryString();
        $data['companies'] = Company::orderBy('name')->get();
        $data['areas'] = Area::with('company')->orderBy('name')->get();

        return view('devices.pending', $data);
    }

    public function assignPending(Request $request)
    {
        $request->validate([
            'sn' => 'required|string|exists:pending_devices,sn',
            'company_id' => 'required|integer|exists:companies,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'comm_key' => 'required|digits_between:4,8',
        ]);

        $pending = PendingDevice::where('sn', $request->sn)->firstOrFail();

        $areaId = $request->area_id;
        if (! $areaId) {
            $areaId = Area::where('company_id', $request->company_id)
                ->where('name', 'like', 'Default%')
                ->orderBy('id')
                ->value('id') ?: Area::where('company_id', $request->company_id)->orderBy('id')->value('id');
        }

        Device::updateOrCreate(
            ['no_sn' => $pending->sn],
            [
                'company_id' => $request->company_id,
                'area_id' => $areaId,
                'nama' => $pending->sn,
                'ip_address' => $pending->ip_address,
                'model' => $pending->model,
                'fw_ver' => $pending->fw_ver,
                'push_ver' => $pending->push_ver,
                'comm_key' => $request->comm_key,
                'status' => Device::STATUS_REGISTERED,
            ]
        );

        $pending->update(['state' => PendingDevice::STATE_ASSIGNED]);

        // Try to process any records that arrived while the device was pending
        DB::table('attendance_staging')
            ->where('sn', $pending->sn)
            ->whereNull('device_id')
            ->update([
                'device_id' => DB::table('devices')->where('no_sn', $pending->sn)->value('id'),
                'company_id' => $request->company_id,
            ]);

        \App\Jobs\ProcessAttendanceStaging::dispatch();

        return redirect()->route('devices.pending')->with('success', 'Device assigned successfully');
    }

    public function ignorePending(Request $request, $id)
    {
        $pending = PendingDevice::findOrFail($id);
        $pending->update(['state' => PendingDevice::STATE_IGNORED]);

        return redirect()->route('devices.pending')->with('success', 'Device ignored');
    }

    public function unassignPending(Request $request, $id)
    {
        $pending = PendingDevice::findOrFail($id);

        if (! in_array($pending->state, [PendingDevice::STATE_ASSIGNED, PendingDevice::STATE_BLOCKED])) {
            return redirect()->route('devices.pending')->with('failed', 'Only assigned devices can be unassigned');
        }

        DB::table('devices')->where('no_sn', $pending->sn)->delete();
        $pending->update(['state' => PendingDevice::STATE_DETECTED]);

        return redirect()->route('devices.pending')->with('success', 'Device unassigned and back to detected');
    }

    public function disablePending(Request $request, $id)
    {
        $pending = PendingDevice::findOrFail($id);

        DB::table('devices')->where('no_sn', $pending->sn)->update(['status' => Device::STATUS_BLOCKED]);
        $pending->update(['state' => PendingDevice::STATE_BLOCKED]);

        return redirect()->route('devices.pending')->with('success', 'Device disabled');
    }

    public function enablePending(Request $request, $id)
    {
        $pending = PendingDevice::findOrFail($id);

        if ($pending->state !== PendingDevice::STATE_BLOCKED) {
            return redirect()->route('devices.pending')->with('failed', 'Only blocked devices can be enabled');
        }

        DB::table('devices')->where('no_sn', $pending->sn)->update(['status' => Device::STATUS_REGISTERED]);
        $pending->update(['state' => PendingDevice::STATE_ASSIGNED]);

        return redirect()->route('devices.pending')->with('success', 'Device enabled');
    }

    public function redetectPending(Request $request, $id)
    {
        $pending = PendingDevice::findOrFail($id);

        if ($pending->state !== PendingDevice::STATE_IGNORED) {
            return redirect()->route('devices.pending')->with('failed', 'Only ignored devices can be re-detected');
        }

        $pending->update(['state' => PendingDevice::STATE_DETECTED]);

        return redirect()->route('devices.pending')->with('success', 'Device re-detected');
    }

    public function DeviceLog(Request $request)
    {
        $data['lable'] = 'Devices Log';
        $perPage = 10;
        $data['log'] = DB::table('device_log')->select('id', 'data', 'url')->orderBy('id', 'DESC')->paginate($perPage);

        return view('devices.log', $data);
    }

    public function FingerLog(Request $request)
    {
        $data['lable'] = 'Finger Log';
        $perPage = 10;
        $data['log'] = DB::table('finger_log')
            ->select('id', 'data', 'url')
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        return view('devices.log', $data);
    }

    public function Attendance(Request $request)
    {
        return view('devices.attendance');
    }

    public function getAttendance(Request $request)
    {
        $query = DB::table('attendances')
            ->leftJoin('employees', function ($join) {
                $join->on('attendances.employee_id', '=', 'employees.employee_id')
                    ->on('attendances.company_id', '=', 'employees.company_id');
            })
            ->select(
                'attendances.id',
                'attendances.sn',
                'attendances.employee_id',
                'attendances.timestamp',
                'attendances.status1',
                'attendances.status2',
                'employees.name as employee_name'
            )
            ->orderBy('attendances.timestamp', 'DESC');

        $companyId = current_company_id();
        if ($companyId) {
            $query->where('attendances.company_id', $companyId);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('attendances.timestamp', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('attendances.timestamp', '<=', $request->end_date);
        }
        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', '=', $request->employee_id);
        }
        if ($request->filled('employee_name')) {
            $query->where('employees.name', 'like', $request->employee_name.'%');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('employee_name', function ($row) {
                return $row->employee_name ?? '';
            })
            ->toJson();
    }

    public function daily(Request $request)
    {
        return view('devices.daily');
    }

    public function getDailyAttendanceSummary(Request $request)
    {
        $companyId = current_company_id();

        // Determine the date to filter
        $start_date = $request->input('start_date');
        // Validate the date input
        if ($start_date && Carbon::canBeCreatedFromFormat($start_date, 'Y-m-d')) {
            $date = Carbon::createFromFormat('Y-m-d', $start_date);
        } else {
            $date = Carbon::yesterday(); // Default to yesterday if the date is invalid
        }

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Query to get all employees
        $employees = Employee::forCompany($companyId)
            ->with('department', 'schedules')
            ->select('id', 'company_id', 'department_id', 'employee_id', 'name')
            ->get();

        // Preload every applicable schedule for the involved companies so the
        // per-employee status loop below runs without N+1 queries.
        $companyIds = $employees->pluck('company_id')->filter()->unique()->map(fn ($id) => (int) $id)->values()->all();
        $statusService = app(AttendanceStatusService::class);
        $companySchedules = $statusService->companySchedulesForDate($companyIds, $date);

        // Query to get attendance data for the specified date
        $attendanceData = DB::table('attendances')
            ->select(
                'employee_id',
                DB::raw('MIN(CASE WHEN status1 = 0 THEN timestamp END) as first_in'),
                DB::raw('MAX(CASE WHEN status1 = 1 THEN timestamp END) as last_out')
            )
            ->when($companyId, function ($query, $companyId) {
                return $query->where('company_id', $companyId);
            })
            ->whereBetween('timestamp', [$startOfDay, $endOfDay])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Prepare the attendance summary
        $attendanceSummary = $employees->map(function ($employee) use ($attendanceData, $statusService, $date, $companySchedules) {

            $attendance = $attendanceData->get($employee->employee_id);
            $firstIn = $attendance && $attendance->first_in ? Carbon::parse($attendance->first_in) : null;
            $lastOut = $attendance && $attendance->last_out ? Carbon::parse($attendance->last_out) : null;
            $totalTime = ($firstIn && $lastOut) ? sprintf('%02d:%02d', $firstIn->diffInHours($lastOut), $firstIn->diffInMinutes($lastOut) % 60) : 'N/A';

            $result = $statusService->statusFor($employee, $date, $firstIn, $lastOut, null, $companySchedules);

            return [
                'employee_id' => $employee->employee_id,
                'employee_name' => $employee->name ?: '',
                'first_in' => $firstIn ? $firstIn->format('d/m/Y, h:i A') : 'No Checkin', // Show N/A if first_in is null
                'last_out' => $lastOut ? $lastOut->format('d/m/Y, h:i A') : 'No Checkout', // Show N/A if last_out is null
                'total_time' => $totalTime,
                'scheduled_in' => $result['scheduled_in'] ? $result['scheduled_in']->format('h:i A') : '-',
                'scheduled_out' => $result['scheduled_out'] ? $result['scheduled_out']->format('h:i A') : '-',
                'status' => $result['status'],
            ];
        });

        return response()->json([
            'data' => $attendanceSummary,
            'startOfDay' => $startOfDay->toDateTimeString(),
            'endOfDay' => $endOfDay->toDateTimeString(),
        ]);
    }

    public function monthly(Request $request)
    {
        return view('devices.monthly');
    }

    public function getMonthlyAttendanceSummary(Request $request)
    {
        $companyId = current_company_id();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $employeeId = $request->input('employee_id');
        $employeeName = $request->input('employee_name');
        // Validate input
        if (! $startDate || ! $endDate || (! $employeeId && ! $employeeName)) {
            return response()->json(['data' => []]); // Return empty array if required inputs are missing
        }
        // Parse dates
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();
        // Find the employee
        $employee = Employee::forCompany($companyId)
            ->with('department', 'schedules')
            ->where(function ($query) use ($employeeId, $employeeName) {
                if ($employeeId) {
                    $query->where('employee_id', $employeeId);
                }
                if ($employeeName) {
                    $query->orWhere('name', 'like', '%'.$employeeName.'%');
                }
            })
            ->first();
        // If employee not found, return empty array
        if (! $employee) {
            return response()->json(['data' => []]);
        }
        // Fetch attendance records for the employee
        $attendanceData = DB::table('attendances')
            ->select(
                DB::raw('DATE(timestamp) as date'),
                DB::raw('MIN(CASE WHEN status1 = 0 THEN timestamp END) as first_in'),
                DB::raw('MAX(CASE WHEN status1 = 1 THEN timestamp END) as last_out')
            )
            ->where('employee_id', $employee->employee_id)
            ->when($companyId, function ($query, $companyId) {
                return $query->where('company_id', $companyId);
            })
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(timestamp)'))
            ->get();
        $statusService = app(AttendanceStatusService::class);

        // Prepare the result
        $result = $attendanceData->map(function ($record) use ($employee, $statusService) {
            $firstIn = $record->first_in ? Carbon::parse($record->first_in) : null;
            $lastOut = $record->last_out ? Carbon::parse($record->last_out) : null;
            $totalTime = ($firstIn && $lastOut) ? $firstIn->diff($lastOut)->format('%H:%I') : 'N/A';

            $statusResult = $statusService->statusFor($employee, Carbon::parse($record->date), $firstIn, $lastOut);

            return [
                'employee_id' => $employee->employee_id,
                'employee_name' => $employee->name,
                'date' => $record->date,
                'first_in' => $firstIn ? $firstIn->format('h:i A') : '-',
                'last_out' => $lastOut ? $lastOut->format('h:i A') : '-',
                'total_hours' => $totalTime,
                'scheduled_in' => $statusResult['scheduled_in'] ? $statusResult['scheduled_in']->format('h:i A') : '-',
                'scheduled_out' => $statusResult['scheduled_out'] ? $statusResult['scheduled_out']->format('h:i A') : '-',
                'status' => $statusResult['status'],
            ];
        });

        return response()->json(['data' => $result]);
    }

    protected function authorizeCompany(?int $companyId): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($companyId && $user->company_id !== $companyId) {
            abort(403);
        }
    }
}
