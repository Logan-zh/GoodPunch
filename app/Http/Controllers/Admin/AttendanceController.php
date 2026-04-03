<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Punch;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        abort_if($companyId === null, 403, 'User is not associated with a company.');

        $today = Carbon::today();
        
        // Stats
        $totalStaff = User::where('company_id', $companyId)->where('role', '!=', 'admin')->count();
        $punchedInToday = Punch::where('company_id', $companyId)->where('type', 'in')->whereDate('punch_time', $today)->distinct('user_id')->count();
        
        // Alerts: Punches out of range (we store lat/lng)
        // Here we'd need to compare with settings, but for simple stats we can count those with lat/lng info
        // or specifically those who failed distance check (but we don't save failed ones).
        // Let's just show total count of punches today.
        $totalPunchesToday = Punch::whereDate('punch_time', $today)->count();

        $query = Punch::with('user')->latest();

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date) {
            $query->whereDate('punch_time', $request->date);
        }

        return Inertia::render('Admin/Attendance/Index', [
            'punches' => $query->paginate(15)->withQueryString(),
            'stats' => [
                'totalStaff' => $totalStaff,
                'punchedInToday' => $punchedInToday,
                'totalPunchesToday' => $totalPunchesToday,
            ],
            'users' => User::where('company_id', $companyId)->select('id', 'name')->get(),
            'filters' => $request->only(['user_id', 'date']),
        ]);
    }

    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;

        abort_if($companyId === null, 403, 'User is not associated with a company.');

        $filename = 'attendance_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new AttendanceExport($companyId, $request->user_id, $request->date),
            $filename
        );
    }
}
