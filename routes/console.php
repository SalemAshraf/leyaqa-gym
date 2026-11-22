<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire')->daily();

// Daily: write due (never auto-charged) renewal invoices for ending cycles.
Schedule::command('gym:process-renewals')->daily();

// Hourly: each opted-in gym fires at its own local 10:00.
Schedule::command('gym:send-reminders')->hourly();
