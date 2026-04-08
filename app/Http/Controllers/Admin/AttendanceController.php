<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Punch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        if ($response = $this->redirectIfCompanyIsMissing($companyId, '無法進入出勤紀錄')) {
            return $response;
        }

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
        $companyId = Auth::user()->company_id;

        if ($response = $this->redirectIfCompanyIsMissing($companyId, '無法匯出出勤紀錄')) {
            return $response;
        }

        $filename = 'attendance_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new AttendanceExport($companyId, $request->user_id, $request->date),
            $filename
        );
    }

    private function redirectIfCompanyIsMissing(?int $companyId, string $title): ?RedirectResponse
    {
        if ($companyId !== null) {
            return null;
        }

        return redirect()->route('dashboard')->with('alert', [
            'type' => 'error',
            'title' => $title,
            'message' => '目前帳號尚未綁定公司，因此無法使用這個管理功能。',
        ]);
    }
}
