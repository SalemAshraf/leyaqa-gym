<?php

namespace App\Services\Messaging;

use RuntimeException;

class Messaging
{
    public static function provider(): MessagingProvider
    {
        return match (config('services.messaging.driver', 'log')) {
            'log' => new LogMessagingProvider(),
            'whatsapp_cloud' => new WhatsAppCloudProvider(),
            default => throw new RuntimeException('Unknown messaging driver.'),
        };
    }
}
