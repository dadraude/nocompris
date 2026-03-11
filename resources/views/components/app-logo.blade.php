@props([
    'sidebar' => false,
])

<a {{ $attributes->class([
    'group inline-flex items-center gap-3 text-left',
    'w-full' => $sidebar,
]) }}>
    <span class="flex size-11 items-center justify-center rounded-2xl bg-linear-to-br from-[#34584f] via-[#4c7f72] to-[#d2965a] text-white shadow-lg shadow-[#34584f]/20 transition-transform duration-200 group-hover:-translate-y-0.5 dark:from-[#c1dccd] dark:via-[#8db39e] dark:to-[#d2965a] dark:text-zinc-950 dark:shadow-[#0a0a0a]/40">
        <x-app-logo-icon class="size-6 fill-current" />
    </span>

    <span class="grid flex-1 leading-tight">
        <span class="font-display text-[1.35rem] font-semibold tracking-[-0.03em] text-zinc-950 dark:text-white">NoCompris</span>
        <span class="text-xs uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
            {{ $sidebar ? __('Compra compartida') : __('Organitza millor la compra') }}
        </span>
    </span>
</a>
