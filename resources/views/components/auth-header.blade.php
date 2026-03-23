@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-3 text-center">
    <div class="flex justify-center">
        <span class="app-chip">{{ __('Espai segur') }}</span>
    </div>

    <div class="space-y-2">
        <flux:heading size="xl">{{ $title }}</flux:heading>
        <flux:subheading class="text-pretty text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $description }}</flux:subheading>
    </div>
</div>
