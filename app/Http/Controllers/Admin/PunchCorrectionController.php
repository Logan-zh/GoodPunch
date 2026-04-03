<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Punch;
use App\Models\PunchCorrection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PunchCorrectionController extends Controller
{
    public function index()
    {
        $manager = Auth::user();

        $subordinateIds = $manager->subordinates()->pluck('id');

        $corrections = PunchCorrection::whereIn('user_id', $subordinateIds)
            ->with(['user', 'approver'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->where('created_at', '>=', now()->subDays(30))
            ->paginate(20);

        return Inertia::render('Admin/PunchCorrections', [
            'corrections' => $corrections,
        ]);
    }

    public function update(Request $request, PunchCorrection $punchCorrection)
    {
        $request->validate([
            'status'  => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->status === 'approved') {
            Punch::updateOrCreate(
                [
                    'user_id'       => $punchCorrection->user_id,
                    'correction_id' => $punchCorrection->id,
                ],
                [
                    'company_id'    => $punchCorrection->company_id,
                    'type'          => $punchCorrection->correction_type,
                    'punch_time'    => $punchCorrection->requested_time,
                    'correction_id' => $punchCorrection->id,
                ]
            );

            // Reprocess attendance for the corrected date
            try {
                $user = $punchCorrection->user;
                if (class_exists(\App\Services\AttendanceService::class)) {
                    app(\App\Services\AttendanceService::class)->processDay(
                        $user,
                        Carbon::parse($punchCorrection->correction_date)
                    );
                }
            } catch (\Throwable) {
                // Non-critical
            }
        }

        $punchCorrection->update([
            'status'        => $request->status,
            'approver_id'   => Auth::id(),
            'approved_at'   => now(),
            'approval_note' => $request->comment,
        ]);

        return back()->with('success', $request->status === 'approved' ? '已核准補打卡申請。' : '已駁回補打卡申請。');
    }
}
