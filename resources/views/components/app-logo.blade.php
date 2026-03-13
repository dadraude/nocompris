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
    <x-app-logo-icon class="size-10 shrink-0 rounded-2xl" />

    <span class="grid flex-1 leading-tight">
        <span class="font-display text-[1.3rem] font-semibold tracking-[-0.03em] text-zinc-950 dark:text-white">NoCompris</span>
        <span class="text-[0.7rem] uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
            {{ $sidebar ? __('Compra compartida') : __('Organitza millor la compra') }}
        </span>
    </span>
</a>
