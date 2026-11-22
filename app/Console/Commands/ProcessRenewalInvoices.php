<?php

namespace App\Console\Commands;

use App\Models\RenewalInvoice;
use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Writes a due renewal invoice for every subscription whose cycle ends within
 * the next 7 days (or ended in the last 60). Deduped per subscription+cycle
 * by the (subscription_id, due_date) unique key. Never charges anything.
 */
class ProcessRenewalInvoices extends Command
{
    protected $signature = 'gym:process-renewals';

    protected $description = 'Generate due (non-charging) renewal invoices for expiring subscription cycles';

    public function handle(): int
    {
        $from = today()->subDays(60);
        $until = today()->addDays(7);
        $created = 0;

        // Opt-in per gym: deploying the feature must never create
        // receivables for a tenant that did not turn it on.
        Subscription::withoutGlobalScopes()
            ->whereHas('gym', fn ($q) => $q->where('renewal_invoices_enabled', true))
            ->whereIn('status', ['active', 'frozen', 'expired'])
            ->whereNotNull('ends_at')
            ->where('price', '>', 0)
            ->whereBetween('ends_at', [$from, $until])
            ->chunkById(200, function ($subs) use (&$created) {
                foreach ($subs as $sub) {
                    $exists = RenewalInvoice::withoutGlobalScopes()
                        ->where('subscription_id', $sub->id)
                        ->whereDate('due_date', $sub->ends_at)
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                    RenewalInvoice::create([
                        'gym_id' => $sub->gym_id,
                        'member_id' => $sub->member_id,
                        'subscription_id' => $sub->id,
                        'amount' => $sub->price,
                        'status' => 'due',
                        'due_date' => $sub->ends_at,
                    ]);
                    $created++;
                }
            });

        $this->info("Created {$created} renewal invoice(s).");

        return self::SUCCESS;
    }
}
