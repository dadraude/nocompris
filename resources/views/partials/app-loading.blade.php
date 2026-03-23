<div
    class="fixed inset-0 z-[120] hidden items-center justify-center bg-[radial-gradient(circle_at_top,rgba(255,157,112,0.22),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(77,164,163,0.18),transparent_26%),linear-gradient(180deg,rgba(247,243,236,0.96),rgba(240,233,223,0.98))] px-6 backdrop-blur-sm transition-opacity duration-300 dark:bg-[radial-gradient(circle_at_top,rgba(255,125,70,0.18),transparent_26%),radial-gradient(circle_at_bottom_right,rgba(127,208,203,0.12),transparent_26%),linear-gradient(180deg,rgba(12,17,23,0.96),rgba(16,25,33,0.98))]"
    data-app-loading-screen
    role="status"
    aria-live="polite"
    aria-label="{{ __('Carregant l\'aplicació') }}"
>
    <div class="app-panel w-full max-w-sm rounded-[2rem] p-7 shadow-xl shadow-zinc-950/10 dark:shadow-black/30" data-app-loading-card>
        <div class="flex flex-col items-center gap-5 text-center">
            <div class="flex size-18 items-center justify-center rounded-[1.6rem] bg-white/88 shadow-sm shadow-zinc-950/10 ring-1 ring-white/80 dark:bg-white/6 dark:ring-white/10">
                <x-app-logo-icon class="size-12" />
            </div>

            <div class="space-y-1">
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">{{ __('Compra compartida') }}</p>
                <p class="font-display text-3xl tracking-[-0.05em] text-zinc-950 dark:text-white">NoCompris</p>
                <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Afinant espais, accions i context perquè comprar sigui més directe.') }}</p>
            </div>

            <div class="w-full space-y-3">
                <div class="h-2 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-white/10">
                    <span class="block h-full w-2/3 animate-pulse rounded-full bg-highlight-400 dark:bg-brand-300"></span>
                </div>

                <div class="flex items-center justify-center gap-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    <span class="size-5 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600 dark:border-white/10 dark:border-t-brand-300" data-app-loading-spinner></span>
                    <span>{{ __('Carregant') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
