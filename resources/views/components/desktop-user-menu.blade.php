<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        class="rounded-2xl border border-black/5 bg-white/75 px-1.5 py-1 shadow-sm backdrop-blur-sm dark:border-white/10 dark:bg-zinc-950/50"
        data-test="sidebar-menu-button"
    />

    <flux:menu class="min-w-72 rounded-3xl border border-black/5 bg-white/95 p-2 shadow-xl shadow-zinc-950/10 dark:border-white/10 dark:bg-zinc-900/95">
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
