<x-layouts::auth.card :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-linear-to-br from-white via-stone-50 to-stone-100 p-6 shadow-sm dark:border-stone-800 dark:from-stone-950 dark:via-stone-950 dark:to-stone-900">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="inline-flex items-center rounded-full border border-stone-200 bg-white/80 px-3 py-1 text-xs font-medium tracking-[0.24em] text-stone-500 uppercase dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-300">
                        {{ __('Email sign in') }}
                    </div>

                    <div class="flex size-10 items-center justify-center rounded-2xl bg-stone-900 text-white dark:bg-white dark:text-stone-900">
                        <flux:icon.envelope variant="mini" class="size-5" />
                    </div>
                </div>

                <div class="space-y-2 text-left">
                    <flux:heading size="xl">{{ __('A faster way back in') }}</flux:heading>
                    <flux:text class="text-pretty text-sm leading-6 text-stone-600 dark:text-stone-300">
                        {{ __('Enter your email and we will send you a one-time code to continue securely.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.email.send') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="grid gap-3 rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 p-4 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900/60 dark:text-stone-300">
                <div class="flex items-center gap-3">
                    <div class="flex size-8 items-center justify-center rounded-full bg-white text-stone-900 shadow-sm dark:bg-stone-800 dark:text-white">
                        <flux:icon.sparkles variant="mini" class="size-4" />
                    </div>

                    <p class="text-balance font-medium text-stone-800 dark:text-stone-100">
                        {{ __('No password to remember, no magic links to chase.') }}
                    </p>
                </div>

                <p class="text-pretty leading-6">
                    {{ __('We will email you a 6-digit code that expires in 10 minutes.') }}
                </p>
            </div>

            <flux:checkbox name="remember" :label="__('Keep me signed in on this device')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Continue with email') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth.card>
