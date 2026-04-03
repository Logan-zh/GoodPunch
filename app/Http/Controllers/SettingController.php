<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        return Inertia::render('Admin/Settings', [
            'office_latitude'  => Setting::get('office_latitude'),
            'office_longitude' => Setting::get('office_longitude'),
            'allowed_radius'   => Setting::get('allowed_radius', 100),
            'company_name'     => $company?->name,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'office_latitude'  => 'nullable|numeric|between:-90,90',
            'office_longitude' => 'nullable|numeric|between:-180,180',
            'allowed_radius'   => 'nullable|integer|min:0|max:50000',
        ]);

        Setting::set('office_latitude',  $data['office_latitude']);
        Setting::set('office_longitude', $data['office_longitude']);
        Setting::set('allowed_radius',   $data['allowed_radius']);

        return back()->with('success', '打卡範圍設定已儲存。');
    }
}
