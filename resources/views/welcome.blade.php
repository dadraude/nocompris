<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-50">
        @include('partials.app-loading')

        <div>
            <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                <x-app-logo href="{{ route('home') }}" wire:navigate />

                <nav class="flex items-center gap-3">
                    @auth
                        <a
                            href="{{ auth()->user()->is_master ? route('master.index') : route('dashboard') }}"
                            class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-900 transition dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                            wire:navigate
                        >
                            {{ auth()->user()->is_master ? __('Gestiona usuaris') : __('Obre la llista') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-900 transition dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                            wire:navigate
                        >
                            {{ __('Inicia sessió') }}
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="rounded-full bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition dark:bg-white dark:text-zinc-950"
                                wire:navigate
                            >
                                {{ __('Crea un compte') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="mx-auto flex w-full max-w-6xl flex-col gap-12 px-4 pb-16 pt-6 sm:px-6 lg:px-8">
                <section class="grid gap-8 border-t border-zinc-200 pt-12 dark:border-zinc-800 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                    <div class="space-y-5">
                        <p class="app-kicker">{{ __('Per famílies, pisos i equips petits') }}</p>
                        <div class="space-y-3">
                            <h1 class="max-w-4xl font-display text-5xl leading-none tracking-[-0.06em] text-zinc-950 sm:text-6xl dark:text-white">
                                {{ __('La llista compartida que converteix el caos en rutina clara.') }}
                            </h1>
                            <p class="max-w-2xl text-base leading-7 text-zinc-600 dark:text-zinc-300">
                                {{ __('NoCompris organitza la compra setmanal per botigues, separa allò públic del que és privat i deixa clar qui ha afegit cada producte i què ja està comprat.') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @auth
                                <a
                                    href="{{ auth()->user()->is_master ? route('master.index') : route('dashboard') }}"
                                    class="rounded-full bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition dark:bg-white dark:text-zinc-950"
                                    wire:navigate
                                >
                                    {{ auth()->user()->is_master ? __('Ves al panell master') : __('Ves al dashboard') }}
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="rounded-full bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition dark:bg-white dark:text-zinc-950"
                                    wire:navigate
                                >
                                    {{ __('Comença ara') }}
                                </a>
                            @endauth

                            <a
                                href="#beneficis"
                                class="rounded-full border border-zinc-200 bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                            >
                                {{ __('Veure beneficis') }}
                            </a>
                        </div>
                    </div>

                    <div class="app-panel p-6">
                        <p class="app-kicker">{{ __('Resum') }}</p>
                        <div class="mt-4 space-y-4 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>{{ __('Crea botigues, afegeix productes i marca què ja tens.') }}</p>
                            <p>{{ __('Comparteix només el que toca amb el teu grup.') }}</p>
                            <p>{{ __('Tot des d’una interfície curta i directa.') }}</p>
                        </div>
                    </div>
                </section>

                <section id="beneficis" class="grid gap-5 md:grid-cols-3">
                    <article class="app-panel p-5">
                        <p class="app-kicker">{{ __('Clar') }}</p>
                        <h2 class="mt-3 font-display text-2xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Una botiga, una conversa.') }}</h2>
                        <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                            {{ __('Cada botiga agrupa els seus productes i evita llistes interminables sense context.') }}
                        </p>
                    </article>

                    <article class="app-panel p-5">
                        <p class="app-kicker">{{ __('Flexible') }}</p>
                        <h2 class="mt-3 font-display text-2xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Privat quan cal, públic quan convé.') }}</h2>
                        <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                            {{ __('No tot ha de ser visible per a tothom: la lògica de visibilitat ja està integrada.') }}
                        </p>
                    </article>

                    <article class="app-panel p-5">
                        <p class="app-kicker">{{ __('Operatiu') }}</p>
                        <h2 class="mt-3 font-display text-2xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Pensat per comprar, no per admirar-lo.') }}</h2>
                        <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                            {{ __('Interfície directa, dades útils i menys fricció quan estàs amb el mòbil davant del lineal.') }}
                        </p>
                    </article>
                </section>
            </main>
        </div>

        @fluxScripts
    </body>
</html>
