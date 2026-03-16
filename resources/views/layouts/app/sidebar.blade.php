<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-50 dark:bg-zinc-950">
        <div class="min-h-screen lg:flex">
            <flux:sidebar collapsible="mobile" class="data-flux-sidebar-on-mobile:top-14! data-flux-sidebar-on-mobile:bottom-0! data-flux-sidebar-on-mobile:min-h-0! data-flux-sidebar-on-mobile:max-h-none! border-e border-zinc-200 bg-white lg:sticky lg:top-0 lg:max-h-dvh lg:overflow-y-auto lg:overscroll-contain dark:border-zinc-800 dark:bg-zinc-950">
                <flux:sidebar.header class="border-b border-zinc-200 px-2.5 py-2.5 dark:border-zinc-800 lg:px-3 lg:py-3">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:sidebar.collapse class="lg:hidden" />
                </flux:sidebar.header>

                <flux:sidebar.nav class="px-2 py-2 lg:px-2.5 lg:py-3">
                    <flux:sidebar.group :heading="__('Espais')" class="grid gap-1">
                        @if (! auth()->user()->is_master)
                            <flux:sidebar.item class="rounded-2xl" icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                                {{ __('Llista de la compra') }}
                            </flux:sidebar.item>
                        @endif
                        @if (auth()->user()->is_master)
                            <flux:sidebar.item class="rounded-2xl" icon="users" :href="route('master.index')" :current="request()->routeIs('master.index')" wire:navigate>
                                {{ __('Usuaris i grups') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                </flux:sidebar.nav>

                <flux:spacer class="hidden lg:block" />

                <div class="hidden px-2.5 pb-2.5 lg:block lg:px-3 lg:pb-3">
                    @if (auth()->user()->is_master)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-2.5 py-2 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                            {{ __('Aquest perfil només gestiona usuaris i grups.') }}
                        </div>
                    @endif

                    <x-desktop-user-menu class="{{ auth()->user()->is_master ? 'mt-2 hidden lg:block' : 'hidden lg:block' }}" :name="auth()->user()->name" />
                </div>
            </flux:sidebar>

            <div class="relative flex min-h-screen flex-1 flex-col">
                <flux:header class="fixed inset-x-0 top-0 z-40 min-h-14 border-b border-zinc-200 bg-white px-2.5 py-2 lg:hidden dark:border-zinc-800 dark:bg-zinc-950">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                    <div class="ml-2 min-w-0">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                            {{ auth()->user()->is_master ? __('Gestió d\'accessos') : __('Compra compartida') }}
                        </p>
                        <p class="truncate font-display text-base tracking-[-0.04em] text-zinc-950 dark:text-white">NoCompris</p>
                    </div>

                    <flux:spacer />

                    <button
                        type="button"
                        class="mr-1 inline-flex size-8 items-center justify-center rounded-md text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-white/15 dark:hover:text-white"
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

                        <flux:menu class="min-w-72 rounded-3xl border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
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

                <div class="relative flex-1 pt-14 lg:pt-0">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
