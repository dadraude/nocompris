<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-100 dark:bg-zinc-950">
        <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(210,150,90,0.2),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(52,88,79,0.18),_transparent_24%),linear-gradient(180deg,_rgba(245,245,244,1),_rgba(244,244,245,0.9))] dark:bg-[radial-gradient(circle_at_top_left,_rgba(210,150,90,0.16),_transparent_26%),radial-gradient(circle_at_bottom_right,_rgba(95,135,120,0.16),_transparent_24%),linear-gradient(180deg,_rgba(9,9,11,1),_rgba(18,18,22,0.96))]">
            <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-72 bg-white/30 blur-3xl dark:bg-white/5 lg:block"></div>

            <flux:sidebar sticky collapsible="mobile" class="border-e border-white/60 bg-white/70 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/70">
                <flux:sidebar.header class="border-b border-black/5 px-4 py-5 dark:border-white/10">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:sidebar.collapse class="lg:hidden" />
                </flux:sidebar.header>

                <div class="px-4 pt-4">
                    <div class="app-panel overflow-hidden bg-linear-to-br from-white via-stone-50 to-[#f3e7d8] p-4 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900">
                        <p class="app-kicker">{{ __('Setmana al dia') }}</p>
                        <p class="mt-2 font-display text-2xl leading-none tracking-[-0.04em] text-zinc-950 dark:text-white">
                            {{ __('Compra amb menys fricció') }}
                        </p>
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Comparteix botigues, reparteix responsabilitats i evita els duplicats a última hora.') }}
                        </p>
                    </div>
                </div>

                <flux:sidebar.nav class="px-3 py-4">
                    <flux:sidebar.group :heading="__('Espais')" class="grid gap-1">
                        <flux:sidebar.item class="rounded-2xl" icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Llista de la compra') }}
                        </flux:sidebar.item>
                        @if (auth()->user()->is_master)
                            <flux:sidebar.item class="rounded-2xl" icon="users" :href="route('master.index')" :current="request()->routeIs('master.index')" wire:navigate>
                                {{ __('Usuaris i grups') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                </flux:sidebar.nav>

                <flux:spacer />

                <div class="px-4 pb-4">
                    <div class="app-subpanel p-4">
                        <p class="app-kicker">{{ __('Flux de treball') }}</p>
                        <div class="mt-3 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>{{ __('1. Crea la botiga on compraràs avui.') }}</p>
                            <p>{{ __('2. Afegeix productes públics o privats segons el context.') }}</p>
                            <p>{{ __('3. Marca el que ja tens per evitar compres duplicades.') }}</p>
                        </div>
                    </div>

                    <x-desktop-user-menu class="mt-4 hidden lg:block" :name="auth()->user()->name" />
                </div>
            </flux:sidebar>

            <div class="relative flex min-h-screen flex-col">
                <flux:header class="border-b border-white/60 bg-white/72 px-4 py-3 backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-zinc-950/70">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                    <div class="ml-2 min-w-0">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Compra compartida') }}</p>
                        <p class="truncate font-display text-xl tracking-[-0.04em] text-zinc-950 dark:text-white">NoCompris</p>
                    </div>

                    <flux:spacer />

                    <flux:dropdown position="top" align="end">
                        <flux:profile
                            :initials="auth()->user()->initials()"
                            icon-trailing="chevron-down"
                        />

                        <flux:menu class="min-w-72 rounded-3xl border border-black/5 bg-white/95 p-2 shadow-xl shadow-zinc-950/10 dark:border-white/10 dark:bg-zinc-900/95">
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <flux:avatar
                                            :name="auth()->user()->name"
                                            :initials="auth()->user()->initials()"
                                        />

                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                @if (auth()->user()->is_master)
                                    <flux:menu.item :href="route('master.index')" icon="users" wire:navigate>
                                        {{ __('Usuaris i grups') }}
                                    </flux:menu.item>
                                @endif
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                    {{ __('Configuració') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer"
                                    data-test="logout-button"
                                >
                                    {{ __('Tanca la sessió') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </flux:header>

                <div class="relative flex-1">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
