<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_company_is_redirected_from_leave_management_with_alert(): void
    {
        $user = User::factory()->admin()->create([
            'company_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('admin.leave-management.index'));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('alert', [
                'type' => 'error',
                'title' => '無法進入請假審核',
                'message' => '目前帳號尚未綁定公司，因此無法查看請假審核。',
            ]);
    }

    public function test_admin_without_company_is_redirected_from_attendance_with_alert(): void
    {
        $user = User::factory()->admin()->create([
            'company_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('admin.attendance.index'));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('alert', [
                'type' => 'error',
                'title' => '無法進入出勤紀錄',
                'message' => '目前帳號尚未綁定公司，因此無法使用這個管理功能。',
            ]);
    }
}
