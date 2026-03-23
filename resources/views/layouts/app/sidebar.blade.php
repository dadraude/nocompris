<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen">
        @include('partials.app-loading')

        <div class="min-h-screen lg:flex">
            <flux:sidebar collapsible="mobile" class="app-sidebar-shell data-flux-sidebar-on-mobile:top-14! data-flux-sidebar-on-mobile:bottom-0! data-flux-sidebar-on-mobile:min-h-0! data-flux-sidebar-on-mobile:max-h-none! lg:sticky lg:top-0 lg:max-h-dvh lg:overflow-y-auto lg:overscroll-contain">
                <flux:sidebar.header class="border-b border-black/6 px-2.5 py-2.5 dark:border-white/8 lg:px-3 lg:py-3">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:sidebar.collapse class="lg:hidden" />
                </flux:sidebar.header>

                <div class="hidden px-3 pt-3 lg:block">
                    <div class="app-hero-shell p-4">
                        <p class="app-kicker">{{ auth()->user()->is_master ? __('Control') : __('Sessió activa') }}</p>
                        <p class="mt-2 font-display text-2xl tracking-[-0.04em] text-zinc-950 dark:text-white">
                            {{ auth()->user()->is_master ? __('Govern de comptes') : __('Compra clara, pas a pas') }}
                        </p>
                        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                            {{ auth()->user()->is_master
                                ? __('Agrupa persones, assigna accessos i mantén cada espai sota control.')
                                : __('Revisa pendents, entra a cada botiga i resol la compra sense perdre context.') }}
                        </p>
                    </div>
                </div>

                <flux:sidebar.nav class="px-2 py-2 lg:px-2.5 lg:py-3">
                    <flux:sidebar.group :heading="__('Espais')" class="grid gap-1">
                        @if (! auth()->user()->is_master)
                            <flux:sidebar.item class="app-sidebar-item" icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate data-current="{{ request()->routeIs('dashboard') ? 'true' : 'false' }}">
                                {{ __('Llista de la compra') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item class="app-sidebar-item" icon="list-bullet" :href="route('shopping-list.full')" :current="request()->routeIs('shopping-list.full')" wire:navigate data-current="{{ request()->routeIs('shopping-list.full') ? 'true' : 'false' }}">
                                {{ __('Llistat complet') }}
                            </flux:sidebar.item>
                        @endif
                        @if (auth()->user()->is_master)
                            <flux:sidebar.item class="app-sidebar-item" icon="users" :href="route('master.index')" :current="request()->routeIs('master.index')" wire:navigate data-current="{{ request()->routeIs('master.index') ? 'true' : 'false' }}">
                                {{ __('Usuaris i grups') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                </flux:sidebar.nav>

                <flux:spacer class="hidden lg:block" />

                <div class="hidden px-2.5 pb-2.5 lg:block lg:px-3 lg:pb-3">
                    @if (auth()->user()->is_master)
                        <div class="app-subpanel px-2.5 py-2 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Aquest perfil només gestiona usuaris i grups.') }}
                        </div>
                    @endif

                    <x-desktop-user-menu class="{{ auth()->user()->is_master ? 'mt-2 hidden lg:block' : 'hidden lg:block' }}" :name="auth()->user()->name" />
                </div>
            </flux:sidebar>

            <div class="relative flex min-h-screen flex-1 flex-col">
                <flux:header class="fixed inset-x-0 top-0 z-40 min-h-14 border-b border-white/80 bg-white/82 px-2.5 py-2 backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-[#0d141b]/86">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                    <div class="ml-2 min-w-0">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">
                            {{ auth()->user()->is_master ? __('Gestió d\'accessos') : __('Compra compartida') }}
                        </p>
                        <p class="truncate font-display text-base tracking-[-0.04em] text-zinc-950 dark:text-white">NoCompris</p>
                    </div>

                    <flux:spacer />

                    <button
                        type="button"
                        class="mr-1 inline-flex size-9 items-center justify-center rounded-2xl text-zinc-500 transition hover:bg-white/90 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-white/12 dark:hover:text-white"
                        onclick="window.location.replace(window.location.href)"
                        aria-label="{{ __('Refresca la pàgina') }}"
                        title="{{ __('Refresca la pàgina') }}"
                        data-test="mobile-refresh-button"
                    >
                        <flux:icon icon="arrow-path" class="size-4" />
                    </button>

                    <flux:dropdown position="top" align="end">
                        <flux:profile
                            :initials="auth()->user()->initials()"
                            icon-trailing="chevron-down"
                        />

                        <flux:menu class="min-w-72 rounded-[1.75rem] border border-white/80 bg-white/92 p-2 shadow-lg backdrop-blur-sm dark:border-white/10 dark:bg-[#121c24]/94">
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
                                @if (! auth()->user()->is_master)
                                    <flux:menu.item :href="route('dashboard')" icon="home" wire:navigate>
                                        {{ __('Llista de la compra') }}
                                    </flux:menu.item>
                                    <flux:menu.item :href="route('shopping-list.full')" icon="list-bullet" wire:navigate>
                                        {{ __('Llistat complet') }}
                                    </flux:menu.item>
                                @endif
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

                <div class="relative flex-1 pt-14 lg:pt-0">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
