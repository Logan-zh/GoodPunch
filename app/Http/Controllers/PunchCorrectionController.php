<?php

namespace App\Http\Controllers;

use App\Models\PunchCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PunchCorrectionController extends Controller
{
    public function index()
    {
        $corrections = PunchCorrection::where('user_id', Auth::id())
            ->with('approver')
            ->latest()
            ->paginate(15);

        return Inertia::render('PunchCorrections', [
            'corrections' => $corrections,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'correction_date' => ['required', 'date'],
            'correction_type' => ['required', 'in:in,out,overtime_in,overtime_out'],
            'requested_time'  => ['required', 'date_format:H:i'],
            'reason'          => ['nullable', 'string', 'max:500'],
        ]);

        Auth::user()->punchCorrections()->create([
            'correction_date' => $request->correction_date,
            'correction_type' => $request->correction_type,
            'requested_time'  => $request->correction_date . ' ' . $request->requested_time . ':00',
            'reason'          => $request->reason,
            'status'          => 'pending',
        ]);

        return back()->with('success', '補打卡申請已送出，等待主管審核。');
    }

    public function destroy(PunchCorrection $punchCorrection)
    {
        abort_unless(
            $punchCorrection->user_id === Auth::id() && $punchCorrection->status === 'pending',
            403
        );

        $punchCorrection->delete();

        return back()->with('success', '補打卡申請已取消。');
    }
}
