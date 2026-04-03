<?php

namespace App\Http\Controllers;

use App\Models\PayrollRecord;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $records = PayrollRecord::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return Inertia::render('Payroll/Index', ['records' => $records]);
    }

    public function slip(PayrollRecord $payrollRecord)
    {
        abort_if($payrollRecord->user_id !== auth()->id(), 403);
        abort_if($payrollRecord->status !== 'confirmed', 403);

        $payrollRecord->load('user.company');

        return view('payroll.slip', ['record' => $payrollRecord]);
    }
}
