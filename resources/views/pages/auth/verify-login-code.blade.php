<x-layouts::auth.card :title="__('Verify your code')">
    <div class="flex flex-col gap-6">
        <div class="overflow-hidden rounded-3xl border border-stone-200 bg-linear-to-br from-white via-stone-50 to-stone-100 p-6 shadow-sm dark:border-stone-800 dark:from-stone-950 dark:via-stone-950 dark:to-stone-900">
            <div class="flex flex-col gap-4 text-left">
                <div class="inline-flex w-fit items-center rounded-full border border-stone-200 bg-white/80 px-3 py-1 text-xs font-medium tracking-[0.24em] text-stone-500 uppercase dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-300">
                    {{ __('Step 2 of 2') }}
                </div>

                <div class="space-y-2">
                    <flux:heading size="xl">{{ __('Check your inbox') }}</flux:heading>
                    <flux:text class="text-pretty text-sm leading-6 text-stone-600 dark:text-stone-300">
                        {{ __('We sent a 6-digit code to :email. Enter it below to finish signing in.', ['email' => $maskedEmail]) }}
                    </flux:text>
                </div>
            </div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.verify.store') }}">
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
                        label="{{ __('Verification code') }}"
                        label:sr-only
                        class="mx-auto"
                    />
                </div>

                @error('code')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                @enderror

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Verify and continue') }}
                </flux:button>
            </div>
        </form>

        <div class="rounded-2xl border border-stone-200 bg-stone-50/80 p-4 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900/60 dark:text-stone-300">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-pretty leading-6">
                    {{ __('Did not receive anything at :email?', ['email' => $email]) }}
                </p>

                <form method="POST" action="{{ route('login.verify.resend') }}">
                    @csrf

                    <flux:button variant="ghost" type="submit">
                        {{ __('Resend code') }}
                    </flux:button>
                </form>
            </div>
        </div>

        <div class="text-center text-sm text-stone-500 dark:text-stone-400">
            <flux:link :href="route('login')">{{ __('Use a different email') }}</flux:link>
        </div>
    </div>
</x-layouts::auth.card>
