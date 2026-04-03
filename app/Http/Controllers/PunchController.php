<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Punch;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Models\Setting;

class PunchController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out,overtime_in,overtime_out',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = Auth::user();

        // Check Range if Configured
        $offLat = Setting::get('office_latitude');
        $offLng = Setting::get('office_longitude');
        $allowedRadius = Setting::get('allowed_radius');

        if ($offLat && $offLng && $allowedRadius) {
            if (!$request->latitude || !$request->longitude) {
                return back()->with('error', '打卡需要開啟定位權限，請允許瀏覽器存取您的位置。');
            }

            $distance = $this->calculateDistance(
                $request->latitude, 
                $request->longitude, 
                $offLat, 
                $offLng
            );

            if ($distance > $allowedRadius) {
                return back()->with('error', '您不在允許的打卡範圍內。目前距離辦公室：' . round($distance) . ' 公尺（限制：' . $allowedRadius . ' 公尺）');
            }
        }
        
        // Prevent duplicate consecutive punch of the same type (only for in/out)
        if (in_array($request->type, ['in', 'out'])) {
            $lastPunch = $user->punches()->whereIn('type', ['in', 'out'])->latest()->first();
            if ($lastPunch && $lastPunch->type === $request->type) {
                return back()->with('error', 'You have already punched ' . $request->type);
            }
        }

        $user->punches()->create([
            'type' => $request->type,
            'punch_time' => Carbon::now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Reprocess attendance for today
        try {
            if (class_exists(\App\Services\AttendanceService::class)) {
                app(\App\Services\AttendanceService::class)->processDay($user, Carbon::today());
            }
        } catch (\Throwable) {
            // Non-critical: attendance reprocessing can fail silently
        }

        return back()->with('success', 'Successfully punched ' . $request->type);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
