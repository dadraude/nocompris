<div
    class="fixed inset-0 z-[120] hidden items-center justify-center bg-stone-50/92 px-6 backdrop-blur-sm transition-opacity duration-300 dark:bg-zinc-950/92"
    data-app-loading-screen
    role="status"
    aria-live="polite"
    aria-label="{{ __('Carregant l\'aplicació') }}"
>
    <div class="app-panel w-full max-w-xs rounded-[2rem] p-6 shadow-xl shadow-zinc-950/10 dark:shadow-black/30" data-app-loading-card>
        <div class="flex flex-col items-center gap-4 text-center">
            <x-app-logo-icon class="size-16 rounded-[1.4rem] shadow-sm shadow-zinc-950/10" />

            <div class="space-y-1">
                <p class="font-display text-2xl tracking-[-0.05em] text-zinc-950 dark:text-white">NoCompris</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Preparant la teva llista compartida...') }}</p>
            </div>

            <div class="flex items-center gap-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                <span class="size-5 animate-spin rounded-full border-2 border-zinc-200 border-t-accent dark:border-zinc-700 dark:border-t-accent" data-app-loading-spinner></span>
                <span>{{ __('Carregant') }}</span>
            </div>
        </div>
    </div>
</div>
