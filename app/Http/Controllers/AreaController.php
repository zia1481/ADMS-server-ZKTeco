<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Company;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $companyId = current_company_id();
        $areas = Area::forCompany($companyId)->orderBy('name')->paginate(10);
        $companies = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('areas.index', compact('areas', 'companies'));
    }

    public function store(Request $request)
    {
        $companyId = $this->resolveCompanyId($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        Area::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        return redirect()->route('areas.index')->with('success', 'Area created successfully');
    }

    public function update(Request $request, Area $area)
    {
        $this->authorizeCompany($area->company_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        $area->update($request->only(['name', 'code', 'description']));

        return redirect()->route('areas.index')->with('success', 'Area updated successfully');
    }

    public function destroy(Area $area)
    {
        $this->authorizeCompany($area->company_id);

        $area->delete();

        return redirect()->route('areas.index')->with('success', 'Area deleted successfully');
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
