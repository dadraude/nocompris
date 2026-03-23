<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased">
        @include('partials.app-loading')

        <div class="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_32rem]">
            <div class="hidden px-10 py-12 lg:flex lg:flex-col lg:justify-between">
                <div class="max-w-xl space-y-8">
                    <x-app-logo href="{{ route('home') }}" wire:navigate />

                    <div class="app-hero-shell p-8">
                        <div class="space-y-5">
                            <p class="app-kicker">{{ __('Compra compartida') }}</p>
                            <h1 class="font-display text-5xl leading-none tracking-[-0.05em] text-zinc-950 dark:text-white">
                                {{ __('El ritme de la compra, amb una sola vista clara.') }}
                            </h1>
                            <p class="max-w-lg text-base leading-7 text-zinc-600 dark:text-zinc-300">
                                {{ __('Botigues, productes i decisions ràpides dins una interfície més neta, més precisa i preparada per al mòbil.') }}
                            </p>

                            <div class="grid gap-3 pt-2 sm:grid-cols-3">
                                <div class="app-metric-card">
                                    <p class="app-kicker">{{ __('Context') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ __('Cada botiga manté el seu color i el seu ordre.') }}</p>
                                </div>
                                <div class="app-metric-card">
                                    <p class="app-kicker">{{ __('Privadesa') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ __('Públic o privat, sense perdre claredat.') }}</p>
                                </div>
                                <div class="app-metric-card">
                                    <p class="app-kicker">{{ __('Mòbil') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ __('Tot pensat per consultar i marcar sobre la marxa.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                    <span class="size-2 rounded-full bg-highlight-400 dark:bg-brand-300"></span>
                    <span>{{ __('Una experiència directa, preparada per entrar, comprar i sortir.') }}</span>
                </div>
            </div>

            <div class="flex items-center justify-center px-6 py-10 sm:px-10">
                <div class="app-auth-shell w-full max-w-md p-6 sm:p-8">
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
