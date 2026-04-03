<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Company;
use Inertia\Inertia;

class CompanyManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Companies/Index', [
            'companies' => Company::withCount(['users', 'punches'])->latest()->paginate(10),
        ]);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return back()->with('success', 'Company deleted successfully.');
    }
}
