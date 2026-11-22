<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark active subscriptions whose end date has passed as expired';

    public function handle(): int
    {
        $count = Subscription::withoutGlobalScope('gym')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now()->toDateString())
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        $this->info("Expired {$count} subscriptions.");

        return self::SUCCESS;
    }
}
