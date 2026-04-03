<?php

namespace App\Jobs;

use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDailyAttendance implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public readonly Carbon $date) {}

    public function handle(AttendanceService $service): void
    {
        Log::info("ProcessDailyAttendance: processing date {$this->date->toDateString()}");
        $service->processAllForDate($this->date);
        Log::info("ProcessDailyAttendance: completed for {$this->date->toDateString()}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessDailyAttendance: job failed for {$this->date->toDateString()}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
