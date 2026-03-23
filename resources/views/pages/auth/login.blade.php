<x-layouts::auth.card :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <div class="app-auth-band">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="app-chip">
                        {{ __('Entrada amb correu') }}
                    </div>

                    <div class="flex size-11 items-center justify-center rounded-[1.1rem] bg-brand-900 text-white shadow-sm dark:bg-brand-200 dark:text-zinc-950">
                        <flux:icon.envelope variant="mini" class="size-5" />
                    </div>
                </div>

                <div class="space-y-2 text-left">
                    <flux:heading size="xl">{{ __('Torna a entrar sense perdre el ritme.') }}</flux:heading>
                    <flux:text class="text-pretty text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ __('Posa el teu correu i t’enviarem un codi temporal per continuar amb una entrada segura i ràpida.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.email.send') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Adreça de correu')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="app-auth-note">
                <div class="flex items-center gap-3">
                    <div class="flex size-8 items-center justify-center rounded-full bg-white text-brand-900 shadow-sm dark:bg-white/10 dark:text-white">
                        <flux:icon.sparkles variant="mini" class="size-4" />
                    </div>

                    <p class="text-balance font-medium text-zinc-800 dark:text-zinc-100">
                        {{ __('Sense contrasenyes per recordar ni passos innecessaris.') }}
                    </p>
                </div>

                <p class="text-pretty leading-6">
                    {{ __('Rebràs un codi de 6 dígits que caduca en 10 minuts.') }}
                </p>
            </div>

            <flux:checkbox name="remember" :label="__('Mantén la sessió iniciada en aquest dispositiu')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Continua amb el correu') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth.card>
