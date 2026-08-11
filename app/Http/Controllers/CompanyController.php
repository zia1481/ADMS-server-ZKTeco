<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::orderBy('name')->paginate(10);

        return view('companies.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'description' => 'nullable|string|max:255',
        ]);

        $company = Company::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        Area::create([
            'company_id' => $company->id,
            'name' => 'Default Area',
            'code' => 'DFLT',
            'description' => 'Default area created with the company.',
        ]);

        return redirect()->route('companies.index')->with('success', 'Company created successfully. A default area was created for the company.');
    }

    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code,' . $company->id,
            'description' => 'nullable|string|max:255',
        ]);

        $company->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('companies.index')->with('success', 'Company updated successfully');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Company deleted successfully');
    }

    public function switch(Request $request, $company = null)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        if (!$company) {
            session()->forget('current_company_id');

            return redirect()->back()->with('success', 'Working on all companies');
        }

        $company = Company::findOrFail($company);

        session(['current_company_id' => $company->id]);

        return redirect()->back()->with('success', 'Working on company: ' . $company->name);
    }
}
