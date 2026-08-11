<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $companyId = current_company_id();
        $departments = Department::forCompany($companyId)->with('parent')->orderBy('name')->paginate(10);
        $allDepartments = Department::forCompany($companyId)->orderBy('name')->get();
        $companies = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('departments.index', compact('departments', 'allDepartments', 'companies'));
    }

    public function store(Request $request)
    {
        $companyId = $this->resolveCompanyId($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string|max:255',
        ]);

        Department::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'code' => $request->code,
            'parent_id' => $request->parent_id,
            'description' => $request->description,
        ]);

        return redirect()->route('departments.index')->with('success', 'Department created successfully');
    }

    public function update(Request $request, Department $department)
    {
        $this->authorizeCompany($department->company_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:departments,id|not_in:' . $department->id,
            'description' => 'nullable|string|max:255',
        ]);

        $department->update($request->only(['name', 'code', 'parent_id', 'description']));

        return redirect()->route('departments.index')->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        $this->authorizeCompany($department->company_id);

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully');
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
