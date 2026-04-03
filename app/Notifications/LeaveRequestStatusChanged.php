<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $newStatus,
        public ?string $comment = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusText = $this->newStatus === 'approved' ? '已核准' : '已拒絕';

        $message = (new MailMessage)
            ->subject("請假申請{$statusText}")
            ->greeting("您好，{$notifiable->name}，")
            ->line("您的請假申請{$statusText}。")
            ->line("假別：{$this->leaveRequest->type} | 時數：{$this->leaveRequest->hours}小時")
            ->line("期間：{$this->leaveRequest->start_at->format('Y-m-d')} ～ {$this->leaveRequest->end_at->format('Y-m-d')}");

        if ($this->comment) {
            $message->line("審核意見：{$this->comment}");
        }

        return $message->action('查看申請', url(route('leave-requests.index')));
    }
}
