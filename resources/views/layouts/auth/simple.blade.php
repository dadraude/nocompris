<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-50 antialiased dark:bg-zinc-950">
        @include('partials.app-loading')

        <div class="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_30rem]">
            <div class="hidden border-e border-zinc-200 px-10 py-12 lg:flex lg:flex-col lg:justify-between dark:border-zinc-800">
                <div class="max-w-xl space-y-8">
                    <x-app-logo href="{{ route('home') }}" wire:navigate />

                    <div class="space-y-4">
                        <p class="app-kicker">{{ __('Llista domèstica compartida') }}</p>
                        <h1 class="font-display text-5xl leading-none tracking-[-0.05em] text-zinc-950 dark:text-white">
                            {{ __('La compra ben organitzada, sense caos al grup.') }}
                        </h1>
                        <p class="max-w-lg text-base leading-7 text-zinc-600 dark:text-zinc-300">
                            {{ __('Botigues compartides, productes públics o privats i un espai clar perquè tothom sàpiga què falta i què ja està resolt.') }}
                        </p>
                    </div>
                </div>

                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Pensat per a ús personal o compartit, sense passos sobrants.') }}
                </div>
            </div>

            <div class="flex items-center justify-center px-6 py-10 sm:px-10">
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
