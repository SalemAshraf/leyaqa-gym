<?php

namespace App\Services\Messaging;

use App\Models\Gym;

interface MessagingProvider
{
    /** Channel name recorded in reminder_logs. */
    public function channel(): string;

    /**
     * Send one member-facing message. Implementations throw on failure;
     * the caller decides whether to record the reminder.
     */
    public function send(Gym $gym, string $toPhone, string $body): void;
}
