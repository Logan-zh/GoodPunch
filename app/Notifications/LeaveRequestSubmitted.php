<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('新請假申請待審核')
            ->greeting("您好，{$notifiable->name}，")
            ->line("{$this->leaveRequest->user->name} 提交了一份請假申請。")
            ->line("假別：{$this->leaveRequest->type} | 時數：{$this->leaveRequest->hours}小時")
            ->line("期間：{$this->leaveRequest->start_at->format('Y-m-d')} ～ {$this->leaveRequest->end_at->format('Y-m-d')}")
            ->action('立即審核', url(route('admin.leave-management.index')))
            ->line('請盡快完成審核。');
    }
}
