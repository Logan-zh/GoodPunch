<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Companies/Index', [
            'companies' => Company::withCount(['users', 'punches'])->latest()->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'tax_id' => 'nullable|string|max:20|unique:companies,tax_id',
            'principal' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        Company::create($request->only(['name', 'code', 'tax_id', 'principal', 'phone', 'address']));

        return back()->with('success', 'Enterprise created successfully.');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return back()->with('success', 'Company deleted successfully.');
    }
}
