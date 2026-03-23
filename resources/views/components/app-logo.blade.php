@props([
    'sidebar' => false,
])

<a
    aria-label="{{ config('app.name') === 'Laravel' ? 'NoCompris' : config('app.name') }}"
    {{ $attributes->class([
    'group inline-flex items-center gap-3 text-left',
    'w-full' => $sidebar,
]) }}
>
    <span class="flex size-11 shrink-0 items-center justify-center rounded-[1.35rem] bg-white/86 shadow-sm ring-1 ring-white/80 transition duration-200 group-hover:-translate-y-0.5 dark:bg-white/7 dark:ring-white/10">
        <x-app-logo-icon class="size-10 shrink-0 rounded-2xl" />
    </span>

    <span class="grid flex-1 leading-tight">
        <span class="font-display text-[1.34rem] font-semibold tracking-[-0.04em] text-zinc-950 dark:text-white">NoCompris</span>
        <span class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">
            {{ $sidebar ? __('Compra clara') : __('Compra compartida, sense soroll') }}
        </span>
    </span>
</a>
