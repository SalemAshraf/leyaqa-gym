<?php

return [
    // Free trial length for new gyms (days). NULL until the product owner sets commercial terms:
    // without it new gyms are simply active and no trial offer is advertised anywhere.
    'trial_days' => ($v = env('PLATFORM_TRIAL_DAYS')) !== null && $v !== '' ? (int) $v : null,

    // Sales contact shown on billing pages (WhatsApp number, international format without +).
    'sales_whatsapp' => env('PLATFORM_SALES_WHATSAPP'),

    // Comma-separated emails allowed into the platform admin area (the SaaS owner).
    'admin_emails' => array_filter(array_map('trim', explode(',', (string) env('PLATFORM_ADMIN_EMAILS', '')))),

    // Grace period after a paid period ends before the app locks (days).
    'grace_days' => (int) env('PLATFORM_GRACE_DAYS', 7),
];
