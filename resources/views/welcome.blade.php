<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-100 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-50">
        <div class="relative overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(210,150,90,0.24),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(52,88,79,0.18),_transparent_24%),linear-gradient(180deg,_rgba(245,245,244,1),_rgba(244,244,245,0.96))] dark:bg-[radial-gradient(circle_at_top_left,_rgba(210,150,90,0.14),_transparent_26%),radial-gradient(circle_at_bottom_right,_rgba(95,135,120,0.16),_transparent_24%),linear-gradient(180deg,_rgba(9,9,11,1),_rgba(18,18,22,0.98))]">
            <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                <x-app-logo href="{{ route('home') }}" wire:navigate />

                <nav class="flex items-center gap-3">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="rounded-full border border-black/10 bg-white/80 px-4 py-2 text-sm font-semibold text-zinc-900 shadow-sm backdrop-blur-sm transition hover:-translate-y-0.5 dark:border-white/10 dark:bg-zinc-900/70 dark:text-white"
                            wire:navigate
                        >
                            {{ __('Obre la llista') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="rounded-full border border-black/10 bg-white/80 px-4 py-2 text-sm font-semibold text-zinc-900 shadow-sm backdrop-blur-sm transition hover:-translate-y-0.5 dark:border-white/10 dark:bg-zinc-900/70 dark:text-white"
                            wire:navigate
                        >
                            {{ __('Inicia sessió') }}
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="rounded-full bg-[#34584f] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#34584f]/20 transition hover:-translate-y-0.5 dark:bg-[#c1dccd] dark:text-zinc-950 dark:shadow-black/30"
                                wire:navigate
                            >
                                {{ __('Crea un compte') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 pb-16 pt-6 sm:px-6 lg:px-8 lg:pb-24 lg:pt-12">
                <section class="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(22rem,0.9fr)] lg:items-center">
                    <div class="space-y-6">
                        <p class="app-kicker">{{ __('Per famílies, pisos i equips petits') }}</p>
                        <div class="space-y-4">
                            <h1 class="max-w-4xl font-display text-5xl leading-none tracking-[-0.06em] text-zinc-950 sm:text-6xl lg:text-7xl dark:text-white">
                                {{ __('La llista compartida que converteix el caos en rutina clara.') }}
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-zinc-600 dark:text-zinc-300">
                                {{ __('NoCompris organitza la compra setmanal per botigues, separa allò públic del que és privat i deixa clar qui ha afegit cada producte i què ja està comprat.') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @auth
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="rounded-full bg-[#34584f] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#34584f]/20 transition hover:-translate-y-0.5 dark:bg-[#c1dccd] dark:text-zinc-950 dark:shadow-black/30"
                                    wire:navigate
                                >
                                    {{ __('Ves al dashboard') }}
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="rounded-full bg-[#34584f] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#34584f]/20 transition hover:-translate-y-0.5 dark:bg-[#c1dccd] dark:text-zinc-950 dark:shadow-black/30"
                                    wire:navigate
                                >
                                    {{ __('Comença ara') }}
                                </a>
                            @endauth

                            <a
                                href="#beneficis"
                                class="rounded-full border border-black/10 bg-white/75 px-5 py-3 text-sm font-semibold text-zinc-900 shadow-sm backdrop-blur-sm transition hover:-translate-y-0.5 dark:border-white/10 dark:bg-zinc-900/70 dark:text-white"
                            >
                                {{ __('Veure beneficis') }}
                            </a>
                        </div>

                        <div class="grid gap-4 pt-4 sm:grid-cols-3">
                            <div class="app-stat">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Botigues compartides') }}</p>
                                <p class="mt-2 font-display text-3xl tracking-[-0.04em] text-zinc-950 dark:text-white">24/7</p>
                            </div>
                            <div class="app-stat">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Productes visibles') }}</p>
                                <p class="mt-2 font-display text-3xl tracking-[-0.04em] text-zinc-950 dark:text-white">{{ __('Públic / Privat') }}</p>
                            </div>
                            <div class="app-stat">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Seguiment') }}</p>
                                <p class="mt-2 font-display text-3xl tracking-[-0.04em] text-zinc-950 dark:text-white">{{ __('En temps real') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="app-panel p-5 sm:p-6">
                        <div class="app-subpanel p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="app-kicker">{{ __('Vista de treball') }}</p>
                                    <h2 class="mt-2 font-display text-3xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Compra setmanal') }}</h2>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    {{ __('Activa') }}
                                </span>
                            </div>

                            <div class="mt-6 grid gap-4">
                                <div class="rounded-[1.4rem] border border-black/5 bg-white/80 p-4 dark:border-white/10 dark:bg-zinc-900/70">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Mercat central') }}</p>
                                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('4 productes pendents') }}</p>
                                        </div>
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ __('Grup') }}
                                        </span>
                                    </div>
                                    <div class="mt-4 space-y-3 text-sm">
                                        <div class="flex items-center justify-between rounded-2xl bg-stone-50 px-3 py-2 dark:bg-zinc-950/70">
                                            <span>{{ __('Tomàquets') }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('x4') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-2xl bg-stone-50 px-3 py-2 dark:bg-zinc-950/70">
                                            <span>{{ __('Llet') }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('x2') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-2xl bg-emerald-50 px-3 py-2 dark:bg-emerald-950/30">
                                            <span class="line-through">{{ __('Pa integral') }}</span>
                                            <span class="text-emerald-700 dark:text-emerald-300">{{ __('Comprat') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-[1.4rem] border border-black/5 bg-white/80 p-4 dark:border-white/10 dark:bg-zinc-900/70">
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visibilitat intel·ligent') }}</p>
                                        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                                            {{ __('Tria què comparteixes amb el grup i què queda només per a tu.') }}
                                        </p>
                                    </div>
                                    <div class="rounded-[1.4rem] border border-black/5 bg-white/80 p-4 dark:border-white/10 dark:bg-zinc-900/70">
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Control en directe') }}</p>
                                        <p class="mt-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                                            {{ __('Marca productes com a comprats i mantén la llista neta mentre esteu comprant.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="beneficis" class="grid gap-5 lg:grid-cols-3">
                    <article class="app-panel p-6">
                        <p class="app-kicker">{{ __('Clar') }}</p>
                        <h2 class="mt-3 font-display text-3xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Una botiga, una conversa.') }}</h2>
                        <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                            {{ __('Cada botiga agrupa els seus productes i evita llistes interminables sense context.') }}
                        </p>
                    </article>

                    <article class="app-panel p-6">
                        <p class="app-kicker">{{ __('Flexible') }}</p>
                        <h2 class="mt-3 font-display text-3xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Privat quan cal, públic quan convé.') }}</h2>
                        <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                            {{ __('No tot ha de ser visible per a tothom: la lògica de visibilitat ja està integrada.') }}
                        </p>
                    </article>

                    <article class="app-panel p-6">
                        <p class="app-kicker">{{ __('Operatiu') }}</p>
                        <h2 class="mt-3 font-display text-3xl tracking-[-0.05em] text-zinc-950 dark:text-white">{{ __('Pensat per comprar, no per admirar-lo.') }}</h2>
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
