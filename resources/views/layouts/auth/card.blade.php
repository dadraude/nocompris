<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased">
        @include('partials.app-loading')

        <div class="flex min-h-svh flex-col items-center justify-center gap-6 px-6 py-8 md:p-10">
            <div class="w-full max-w-5xl">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_28rem]">
                    <div class="hidden lg:block">
                        <div class="app-hero-shell h-full p-8">
                            <div class="flex h-full flex-col justify-between gap-8">
                                <div class="space-y-6">
                                    <x-app-logo href="{{ route('home') }}" wire:navigate />

                                    <div class="space-y-4">
                                        <p class="app-kicker">{{ __('Accés ràpid') }}</p>
                                        <h1 class="font-display text-5xl leading-none tracking-[-0.05em] text-zinc-950 dark:text-white">
                                            {{ __('Una entrada més clara per a la compra compartida de cada dia.') }}
                                        </h1>
                                        <p class="max-w-lg text-base leading-7 text-zinc-600 dark:text-zinc-300">
                                            {{ __('NoCompris posa context visual, accions directes i una identitat més sòlida a cada pas d’entrada.') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid gap-3">
                                    <div class="app-metric-card">
                                        <p class="app-kicker">{{ __('Entrada') }}</p>
                                        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ __('Correu, codi temporal i, si cal, segon factor. Sense passos sobrants.') }}</p>
                                    </div>
                                    <div class="app-metric-card">
                                        <p class="app-kicker">{{ __('Després') }}</p>
                                        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ __('Redirecció directa a la llista o al panell master segons el teu rol.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-6">
                        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                            <span class="flex h-11 w-11 items-center justify-center rounded-[1.25rem] bg-white/86 shadow-sm ring-1 ring-white/80 dark:bg-white/7 dark:ring-white/10">
                                <x-app-logo-icon class="size-9" />
                            </span>

                            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                        </a>

                        <div class="app-auth-shell text-stone-800">
                            <div class="px-6 py-7 sm:px-10 sm:py-8">{{ $slot }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
