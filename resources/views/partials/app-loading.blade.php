<div
    class="fixed inset-0 z-[120] hidden items-center justify-center bg-[radial-gradient(circle_at_top,rgba(193,220,205,0.35),transparent_32%),linear-gradient(180deg,rgba(250,250,249,0.96),rgba(245,245,244,0.98))] px-6 backdrop-blur-sm transition-opacity duration-300 dark:bg-[radial-gradient(circle_at_top,rgba(193,220,205,0.16),transparent_28%),linear-gradient(180deg,rgba(9,9,11,0.96),rgba(24,24,27,0.98))]"
    data-app-loading-screen
    role="status"
    aria-live="polite"
    aria-label="{{ __('Carregant l\'aplicació') }}"
>
    <div class="app-panel w-full max-w-sm rounded-[2rem] p-7 shadow-xl shadow-zinc-950/10 dark:shadow-black/30" data-app-loading-card>
        <div class="flex flex-col items-center gap-5 text-center">
            <div class="flex size-18 items-center justify-center rounded-[1.6rem] bg-white shadow-sm shadow-zinc-950/10 ring-1 ring-zinc-200/80 dark:bg-zinc-900 dark:ring-zinc-700/70">
                <x-app-logo-icon class="size-12" />
            </div>

            <div class="space-y-1">
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Compra compartida') }}</p>
                <p class="font-display text-3xl tracking-[-0.05em] text-zinc-950 dark:text-white">NoCompris</p>
                <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Preparant la teva llista perquè l’entrada sigui més suau...') }}</p>
            </div>

            <div class="w-full space-y-3">
                <div class="h-2 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-zinc-800/80">
                    <span class="block h-full w-2/3 animate-pulse rounded-full bg-accent/80 dark:bg-accent"></span>
                </div>

                <div class="flex items-center justify-center gap-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    <span class="size-5 animate-spin rounded-full border-2 border-zinc-200 border-t-accent dark:border-zinc-700 dark:border-t-accent" data-app-loading-spinner></span>
                    <span>{{ __('Carregant') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
