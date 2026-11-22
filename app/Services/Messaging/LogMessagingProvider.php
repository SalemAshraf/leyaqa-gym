<?php

namespace App\Services\Messaging;

use App\Models\Gym;
use Illuminate\Support\Facades\Log;

/**
 * Default driver: nothing leaves the system. Messages are written to the
 * app log so gyms can enable and test reminder selection before any
 * WhatsApp/SMS provider account exists.
 */
class LogMessagingProvider implements MessagingProvider
{
    public function channel(): string
    {
        return 'log';
    }

    public function send(Gym $gym, string $toPhone, string $body): void
    {
        Log::channel('single')->info('reminder', [
            'gym_id' => $gym->id,
            'to' => $toPhone,
            'body' => $body,
        ]);
    }
}
