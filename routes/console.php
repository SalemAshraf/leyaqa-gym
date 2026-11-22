<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire')->daily();

// Hourly: each opted-in gym fires at its own local 10:00.
Schedule::command('gym:send-reminders')->hourly();
