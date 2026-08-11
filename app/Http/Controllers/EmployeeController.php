<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $companyId = current_company_id();

        $query = Employee::forCompany($companyId)->with('department');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', $request->name . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->orderBy('name')->paginate(10)->withQueryString();
        $departments = Department::forCompany($companyId)->orderBy('name')->get();
        $companies = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('employees.index', compact('employees', 'departments', 'companies'));
    }

    public function store(Request $request)
    {
        $companyId = $this->resolveCompanyId($request);

        $request->validate([
            'employee_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
        ]);

        Employee::updateOrCreate(
            ['company_id' => $companyId, 'employee_id' => $request->employee_id],
            [
                'name' => $request->name,
                'department_id' => $request->department_id,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
            ]
        );

        return redirect()->route('employees.index')->with('success', 'Employee created successfully');
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeCompany($employee->company_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
        ]);

        $employee->update($request->only(['name', 'department_id', 'email', 'phone', 'position']));

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeCompany($employee->company_id);

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully');
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
