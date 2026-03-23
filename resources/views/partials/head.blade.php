@php
    $applicationName = config('app.name') === 'Laravel' ? 'NoCompris' : config('app.name');
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="application-name" content="{{ $applicationName }}" />
<meta name="description" content="Llista de la compra compartida per organitzar botigues i productes des del mòbil o l'escriptori." />
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#fafaf9" />
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#09090b" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="{{ $applicationName }}" />

<title>
    {{ filled($title ?? null) ? $title.' - '.$applicationName : $applicationName }}
</title>

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|instrument-sans:400,500,600,700" rel="stylesheet" />

<script>
    document.documentElement.classList.add('js', 'app-is-loading');
</script>

<style>
    :root {
        color-scheme: light;
    }

    html {
        background: #fafaf9;
    }

    body {
        min-height: 100vh;
        margin: 0;
        background: #fafaf9;
        color: #18181b;
    }

    .dark {
        color-scheme: dark;
        background: #09090b;
    }

    .dark body {
        background: #09090b;
        color: #f5f5f5;
    }

    [data-app-loading-screen] {
        position: fixed;
        inset: 0;
        z-index: 120;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(250, 250, 249, 0.94);
        color: #18181b;
        opacity: 1;
        transition: opacity 250ms ease;
    }

    .dark [data-app-loading-screen] {
        background: rgba(9, 9, 11, 0.94);
        color: #f5f5f5;
    }

    .js [data-app-loading-screen] {
        display: flex;
    }

    .js:not(.app-is-loading) [data-app-loading-screen] {
        opacity: 0;
        pointer-events: none;
    }

    [data-app-loading-screen][hidden] {
        display: none;
    }

    [data-app-loading-card] {
        width: min(100%, 20rem);
        border: 1px solid rgba(212, 212, 216, 0.92);
        border-radius: 2rem;
        background: rgba(255, 255, 255, 0.96);
        padding: 1.5rem;
        box-shadow: 0 25px 60px rgba(24, 24, 27, 0.12);
    }

    .dark [data-app-loading-card] {
        border-color: rgba(63, 63, 70, 0.85);
        background: rgba(24, 24, 27, 0.96);
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
    }

    [data-app-loading-spinner] {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid rgba(212, 212, 216, 0.95);
        border-top-color: #c1dccd;
        border-radius: 9999px;
        animation: app-loading-spin 0.8s linear infinite;
    }

    .dark [data-app-loading-spinner] {
        border-color: rgba(82, 82, 91, 0.9);
        border-top-color: #c1dccd;
    }

    @keyframes app-loading-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
