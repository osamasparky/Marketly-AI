<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketly-AI — Your Autonomous AI Marketing Employee</title>
    
    <!-- Meta tags for SEO & Social -->
    <meta name="description" content="AI-native multi-tenant marketing SaaS that plans, generates, schedules, publishes, and optimizes multi-platform content.">
    <meta name="theme-color" content="#064e3b">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased min-h-screen">
    <div id="app"></div>
</body>
</html>
