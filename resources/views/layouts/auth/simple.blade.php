<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-100 antialiased dark:bg-zinc-950">
        <div class="relative grid min-h-svh overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(210,150,90,0.24),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(52,88,79,0.18),_transparent_24%),linear-gradient(180deg,_rgba(245,245,244,1),_rgba(244,244,245,0.96))] dark:bg-[radial-gradient(circle_at_top_left,_rgba(210,150,90,0.14),_transparent_26%),radial-gradient(circle_at_bottom_right,_rgba(95,135,120,0.16),_transparent_24%),linear-gradient(180deg,_rgba(9,9,11,1),_rgba(18,18,22,0.98))] lg:grid-cols-[1.05fr_minmax(0,30rem)]">
            <div class="relative hidden p-10 lg:flex lg:flex-col lg:justify-between">
                <div class="max-w-xl space-y-8">
                    <x-app-logo href="{{ route('home') }}" wire:navigate />

                    <div class="space-y-5">
                        <p class="app-kicker">{{ __('Llista domèstica compartida') }}</p>
                        <h1 class="font-display text-5xl leading-none tracking-[-0.05em] text-zinc-950 dark:text-white">
                            {{ __('La compra ben organitzada, sense caos al grup.') }}
                        </h1>
                        <p class="max-w-lg text-lg text-zinc-600 dark:text-zinc-300">
                            {{ __('Botigues compartides, productes públics o privats i un espai clar perquè tothom sàpiga què falta i què ja està resolt.') }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="app-panel p-5">
                        <p class="app-kicker">{{ __('Pensat per equips petits') }}</p>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Ideal per pisos compartits, famílies i qualsevol grup que fa compres recurrents i vol menys missatges improvisats.') }}
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="app-stat">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Botigues') }}</p>
                            <p class="mt-2 font-display text-3xl tracking-[-0.04em] text-zinc-950 dark:text-white">01</p>
                        </div>
                        <div class="app-stat">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visibilitat') }}</p>
                            <p class="mt-2 font-display text-3xl tracking-[-0.04em] text-zinc-950 dark:text-white">02</p>
                        </div>
                        <div class="app-stat">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Control') }}</p>
                            <p class="mt-2 font-display text-3xl tracking-[-0.04em] text-zinc-950 dark:text-white">03</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative flex items-center justify-center px-6 py-10 sm:px-10">
                <div class="app-panel w-full max-w-md p-6 sm:p-8">
                    <div class="mb-6 lg:hidden">
                        <x-app-logo href="{{ route('home') }}" wire:navigate />
                    </div>

                    <div class="flex flex-col gap-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
