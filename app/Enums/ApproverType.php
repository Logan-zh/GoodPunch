<?php

namespace App\Enums;

enum ApproverType: string
{
    case Supervisor = 'supervisor';
    case User       = 'user';

    public function labelKey(): string
    {
        return match($this) {
            self::Supervisor => 'settings.approver_type_supervisor',
            self::User       => 'settings.approver_type_user',
        };
    }
}
