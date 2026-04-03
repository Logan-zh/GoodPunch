<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApproverType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Setting;
use App\Models\User;
use App\Models\LeavePolicy;
use Inertia\Inertia;

class LeavePolicyController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        $holidaysRaw = $company
            ? Setting::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->where('key', 'holidays')
                ->value('value')
            : null;

        return Inertia::render('Admin/Settings/LeavePolicies', [
            'policies' => LeavePolicy::orderBy('type')->orderBy('min_years')->get(),
            'company' => $company,
            'holidays' => $holidaysRaw ? (json_decode($holidaysRaw, true) ?? []) : [],
            'availableApprovers' => $company ? User::where('company_id', $company->id)
                ->whereIn('role', ['admin', 'manager'])
                ->select('id', 'name')
                ->get() : [],
            'approverTypes' => collect(ApproverType::cases())->map(fn($t) => [
                'value'     => $t->value,
                'label_key' => $t->labelKey(),
            ])->values(),
        ]);
    }

    public function updateCompanySettings(Request $request)
    {
        $validated = $request->validate([
            'work_hours_per_day' => 'required|numeric|min:1|max:24',
            'work_start_time'    => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'work_end_time'      => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'leave_approval_chain' => 'nullable|array',
            'leave_approval_chain.*.type' => ['required', Rule::enum(ApproverType::class)],
            'leave_approval_chain.*.name' => 'required|string|max:50',
            'leave_approval_chain.*.user_id' => 'nullable|exists:users,id',
        ]);

        $company = auth()->user()->company;
        
        if (!$company) {
            return back()->with('error', 'User is not associated with a company.');
        }

        $company->update($validated);

        return back()->with('success', 'Company leave settings updated.');
    }

    public function updateHolidays(Request $request)
    {
        $validated = $request->validate([
            'holidays'   => 'required|array',
            'holidays.*' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        $company = auth()->user()->company;

        if (!$company) {
            return back()->with('error', 'User is not associated with a company.');
        }

        Setting::withoutGlobalScope('company')->updateOrCreate(
            ['company_id' => $company->id, 'key' => 'holidays'],
            ['value' => json_encode(array_values(array_unique($validated['holidays'])))]
        );

        return back()->with('success', '國定假日已儲存。');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:annual,personal,sick',
            'min_years' => 'required|numeric|min:0',
            'days' => 'required|integer|min:0',
        ]);

        // For personal/sick, we usually only have one record. Update if exists.
        if (in_array($validated['type'], ['personal', 'sick'])) {
            LeavePolicy::updateOrCreate(
                ['type' => $validated['type']],
                ['days' => $validated['days'], 'min_years' => 0]
            );
        } else {
            // For annual leave, we can have multiple tiers.
            LeavePolicy::updateOrCreate(
                ['type' => 'annual', 'min_years' => $validated['min_years']],
                ['days' => $validated['days']]
            );
        }

        return back()->with('success', 'Leave policy updated.');
    }

    public function destroy(LeavePolicy $leavePolicy)
    {
        $leavePolicy->delete();
        return back()->with('success', 'Tier removed.');
    }
}
