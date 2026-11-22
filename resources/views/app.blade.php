<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Gamed') }}</title>

        {{-- Crawler-visible defaults (no SSR): the landing page is Arabic-first. --}}
        <meta name="description" content="جامد — أعضاء، اشتراكات، حضور QR، حصص وقوائم انتظار، مدفوعات، تقارير، وعملاء محتملون. أدر جيمك بالكامل من شاشة واحدة، بالعربي والإنجليزي.">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Gamed') }}">
        <meta property="og:title" content="جامد — إدارة جيمك من مكان واحد">
        <meta property="og:description" content="أعضاء، اشتراكات، حضور QR، حصص وقوائم انتظار، مدفوعات، تقارير، وعملاء محتملون — بالعربي والإنجليزي.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta name="twitter:card" content="summary">
        <link rel="canonical" href="{{ url()->current() }}">

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
