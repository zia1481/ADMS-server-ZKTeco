<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\Shift;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public const WEEK_DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    public function index()
    {
        $companyId = current_company_id();
        $schedules = Schedule::forCompany($companyId)->with('shift', 'department')->orderBy('name')->paginate(10);
        $shifts = Shift::forCompany($companyId)->orderBy('name')->get();
        $departments = Department::forCompany($companyId)->orderBy('name')->get();
        $weekDays = self::WEEK_DAYS;
        $companies = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('schedules.index', compact('schedules', 'shifts', 'departments', 'weekDays', 'companies'));
    }

    public function store(Request $request)
    {
        $companyId = $this->resolveCompanyId($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'shift_id' => 'required|exists:shifts,id',
            'department_id' => 'nullable|exists:departments,id',
            'working_days' => 'nullable|array',
            'working_days.*' => 'in:0,1,2,3,4,5,6',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        Schedule::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'shift_id' => $request->shift_id,
            'department_id' => $request->department_id,
            'working_days' => $this->normalizeWorkingDays($request->working_days),
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('schedules.index')->with('success', 'Schedule created successfully');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $this->authorizeCompany($schedule->company_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'shift_id' => 'required|exists:shifts,id',
            'department_id' => 'nullable|exists:departments,id',
            'working_days' => 'nullable|array',
            'working_days.*' => 'in:0,1,2,3,4,5,6',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $schedule->update([
            'name' => $request->name,
            'shift_id' => $request->shift_id,
            'department_id' => $request->department_id,
            'working_days' => $this->normalizeWorkingDays($request->working_days),
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully');
    }

    public function destroy(Schedule $schedule)
    {
        $this->authorizeCompany($schedule->company_id);

        $schedule->delete();

        return redirect()->route('schedules.index')->with('success', 'Schedule deleted successfully');
    }

    protected function normalizeWorkingDays($days): array
    {
        return array_map('intval', $days ?? []);
    }

    protected function resolveCompanyId(Request $request): int
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $request->filled('company_id')
                ? $request->input('company_id')
                : current_company_id();
        }

        return $user->company_id;
    }

    protected function authorizeCompany(int $companyId): void
    {
        if (!auth()->user()->isSuperAdmin() && auth()->user()->company_id !== $companyId) {
            abort(403);
        }
    }
}
