<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $adminStats = null;
        if (in_array($user->role, ['admin', 'manager'])) {
            $adminStats = [
                'total_staff'       => User::where('role', 'user')->count(),
                'present_today'     => Punch::whereDate('punch_time', today())
                                          ->where('type', 'in')
                                          ->distinct('user_id')
                                          ->count('user_id'),
                'on_leave_today'    => LeaveRequest::where('status', 'approved')
                                          ->whereDate('start_at', '<=', today())
                                          ->whereDate('end_at', '>=', today())
                                          ->count(),
                'pending_approvals' => LeaveRequest::where('status', 'pending')->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'punches'           => $user->punches()->latest()->paginate(10),
            'lastPunch'         => $user->punches()->latest()->first(),
            'leaveEntitlements' => $user->leave_entitlements,
            'adminStats'        => $adminStats,
        ]);
    }
}
