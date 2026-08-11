<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $companyId = current_company_id();
        $shifts = Shift::forCompany($companyId)->orderBy('name')->paginate(10);
        $companies = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('shifts.index', compact('shifts', 'companies'));
    }

    public function store(Request $request)
    {
        $companyId = $this->resolveCompanyId($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'grace_late_minutes' => 'nullable|integer|min:0',
            'grace_early_leave_minutes' => 'nullable|integer|min:0',
        ]);

        Shift::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'code' => $request->code,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'break_start' => $request->break_start,
            'break_end' => $request->break_end,
            'grace_late_minutes' => $request->grace_late_minutes ?? 0,
            'grace_early_leave_minutes' => $request->grace_early_leave_minutes ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('shifts.index')->with('success', 'Shift created successfully');
    }

    public function update(Request $request, Shift $shift)
    {
        $this->authorizeCompany($shift->company_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'grace_late_minutes' => 'nullable|integer|min:0',
            'grace_early_leave_minutes' => 'nullable|integer|min:0',
        ]);

        $shift->update([
            'name' => $request->name,
            'code' => $request->code,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'break_start' => $request->break_start,
            'break_end' => $request->break_end,
            'grace_late_minutes' => $request->grace_late_minutes ?? 0,
            'grace_early_leave_minutes' => $request->grace_early_leave_minutes ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('shifts.index')->with('success', 'Shift updated successfully');
    }

    public function destroy(Shift $shift)
    {
        $this->authorizeCompany($shift->company_id);

        $shift->delete();

        return redirect()->route('shifts.index')->with('success', 'Shift deleted successfully');
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
