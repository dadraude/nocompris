<x-layouts::auth.card :title="__('Verify your code')">
    <div class="flex flex-col gap-6">
        <div class="app-auth-band">
            <div class="flex flex-col gap-4 text-left">
                <div class="app-chip w-fit">
                    {{ __('Pas 2 de 2') }}
                </div>

                <div class="space-y-2">
                    <flux:heading size="xl">{{ __('Revisa la safata d’entrada') }}</flux:heading>
                    <flux:text class="text-pretty text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ __('Hem enviat un codi de 6 dígits a :email. Introdueix-lo per acabar d’entrar.', ['email' => $maskedEmail]) }}
                    </flux:text>
                </div>
            </div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.verify.store') }}" class="space-y-5">
            @csrf

            <div
                class="space-y-5 text-center"
                x-data="{ code: @js(old('code', '')) }"
            >
                <div class="flex items-center justify-center">
                    <flux:otp
                        x-model="code"
                        length="6"
                        name="code"
                        label="{{ __('Codi de verificació') }}"
                        label:sr-only
                        class="mx-auto"
                    />
                </div>

                @error('code')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                @enderror

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Verifica i continua') }}
                </flux:button>
            </div>
        </form>

        <div class="app-auth-note">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-pretty leading-6">
                    {{ __('No t’ha arribat res a :email?', ['email' => $email]) }}
                </p>

                <form method="POST" action="{{ route('login.verify.resend') }}">
                    @csrf

                    <flux:button variant="ghost" type="submit">
                        {{ __('Torna a enviar el codi') }}
                    </flux:button>
                </form>
            </div>
        </div>

        <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
            <flux:link :href="route('login')">{{ __('Fes servir un altre correu') }}</flux:link>
        </div>
    </div>
</x-layouts::auth.card>
