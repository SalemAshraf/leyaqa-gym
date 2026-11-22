<?php

namespace App\Services\Messaging;

use App\Models\Gym;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meta WhatsApp Cloud API driver. Inert until the platform operator sets
 * WHATSAPP_CLOUD_TOKEN and WHATSAPP_CLOUD_PHONE_NUMBER_ID and switches
 * MESSAGING_DRIVER=whatsapp_cloud - those come from the owner's own Meta
 * business account, so nothing here creates accounts or spends money.
 */
class WhatsAppCloudProvider implements MessagingProvider
{
    public function channel(): string
    {
        return 'whatsapp_cloud';
    }

    public function send(Gym $gym, string $toPhone, string $body): void
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        if (! $token || ! $phoneNumberId) {
            throw new RuntimeException('WhatsApp Cloud API is not configured.');
        }

        $response = Http::withToken($token)
            ->timeout(15)
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $toPhone,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp Cloud API rejected the message: '.$response->status());
        }
    }
}
