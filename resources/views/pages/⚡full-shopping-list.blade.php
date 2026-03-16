<?php

use App\Models\Shop;
use App\Models\ShoppingListItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Llistat complet')] class extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $sortMode = 'shop';

    /**
     * Get the shops and visible items for the authenticated user.
     */
    #[Computed]
    public function shops(): Collection
    {
        $user = Auth::user();

        return Shop::query()
            ->visibleTo($user)
            ->with([
                'shoppingListItems' => fn ($query) => $query
                    ->visibleTo($user)
                    ->with('user'),
            ])
            ->orderBy('position')
            ->get();
    }

    /**
     * Get every visible item in shop order.
     */
    #[Computed]
    public function items(): SupportCollection
    {
        return $this->shops
            ->flatMap(function (Shop $shop): SupportCollection {
                return $shop->shoppingListItems
                    ->map(fn (ShoppingListItem $item): ShoppingListItem => $item->setRelation('shop', $shop));
            })
            ->values();
    }

    /**
     * Get the visible items in alphabetical order.
     */
    #[Computed]
    public function alphabeticalItems(): SupportCollection
    {
        return $this->items
            ->sortBy(fn (ShoppingListItem $item): string => sprintf(
                '%s|%s|%010d',
                Str::lower($item->name),
                Str::lower($item->shop->name),
                $item->position,
            ), SORT_NATURAL)
            ->values();
    }

    /**
     * Get the visible items in the selected display order.
     */
    #[Computed]
    public function orderedItems(): SupportCollection
    {
        return $this->sortMode === 'alphabetical'
            ? $this->alphabeticalItems
            : $this->items;
    }

    /**
     * Keep the sort mode within the supported values.
     */
    public function updatedSortMode(string $value): void
    {
        if (! in_array($value, ['shop', 'alphabetical'], true)) {
            $this->sortMode = 'shop';
        }
    }

    /**
     * Toggle the purchased state for an item.
     */
    public function togglePurchased(int $itemId): void
    {
        $item = $this->findItem($itemId);

        $this->authorize('update', $item);

        $item->update([
            'purchased' => ! $item->purchased,
        ]);
    }

    /**
     * Build the CSS rules used by the shop badge.
     */
    public function shopBadgeStyle(Shop $shop): string
    {
        [$red, $green, $blue] = $this->rgbChannelsFromHex($shop->color);
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;
        $textColor = $brightness >= 160 ? 'rgb(24, 24, 27)' : 'rgb(250, 250, 250)';

        return implode('; ', [
            "background-color: rgb({$red}, {$green}, {$blue})",
            "color: {$textColor}",
        ]).';';
    }

    /**
     * Get the label initial used by the shop badge.
     */
    public function shopInitial(Shop $shop): string
    {
        $initial = Str::of($shop->name)->trim()->substr(0, 1)->upper()->value();

        return $initial !== '' ? $initial : '?';
    }

    /**
     * Convert a hex color into decimal RGB channels.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected function rgbChannelsFromHex(?string $color): array
    {
        $normalizedColor = '#'.Str::lower(ltrim((string) $color, '#'));

        if (! preg_match('/^#[0-9a-f]{6}$/', $normalizedColor)) {
            $normalizedColor = '#d6d3d1';
        }

        $normalizedColor = ltrim($normalizedColor, '#');

        return [
            hexdec(substr($normalizedColor, 0, 2)),
            hexdec(substr($normalizedColor, 2, 2)),
            hexdec(substr($normalizedColor, 4, 2)),
        ];
    }

    /**
     * Find a shopping list item by its identifier.
     */
    protected function findItem(int $itemId): ShoppingListItem
    {
        return ShoppingListItem::query()
            ->with(['shop', 'user'])
            ->visibleTo(Auth::user())
            ->findOrFail($itemId);
    }
};
?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-[90rem] flex-col gap-3 px-2.5 pb-3 pt-1.5 sm:gap-4 sm:px-4 sm:pb-4 sm:pt-2 lg:px-5 xl:px-6">
        <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-linear-to-br from-white via-zinc-50 to-stone-100 shadow-sm dark:border-zinc-700/70 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 sm:rounded-2xl">
            <div class="flex flex-col gap-4 px-3 py-3 sm:px-4 sm:py-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl space-y-1">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Vista global') }}</p>
                    <flux:heading size="xl" level="1">{{ __('Llistat complet') }}</flux:heading>
                    <flux:subheading class="max-w-2xl text-sm">
                        {{ __('Consulta tots els productes visibles d’un cop, ordenats per botiga o alfabèticament, i marca’ls com a comprats sense obrir cada botiga.') }}
                    </flux:subheading>
                </div>

                <div class="grid gap-2 sm:min-w-64">
                    <flux:select wire:model.live="sortMode" :label="__('Organitza per')" data-test="full-list-sort-select">
                        <option value="shop">{{ __('Botiga') }}</option>
                        <option value="alphabetical">{{ __('Ordre alfabètic') }}</option>
                    </flux:select>
                </div>
            </div>
        </div>

        @if ($this->items->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white/80 px-4 py-6 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60 sm:rounded-2xl">
                <flux:heading size="lg">{{ __('Encara no tens productes visibles') }}</flux:heading>
                <flux:text class="mt-3 text-zinc-500 dark:text-zinc-400">
                    {{ __('Quan afegeixis productes a la llista, els veuràs aquí amb una vista compacta.') }}
                </flux:text>
            </div>
        @else
            <div
                data-test="full-list"
                class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/85 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/65 sm:rounded-2xl"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200/70 px-3 py-3 dark:border-zinc-700/70 sm:px-4">
                    <div class="space-y-1">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">
                            {{ __('Ordre actual') }}
                        </p>
                        <flux:heading size="lg">
                            {{ $sortMode === 'alphabetical' ? __('Productes de la A a la Z') : __('Productes segons l’ordre de botiga') }}
                        </flux:heading>
                    </div>

                    <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-sm font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ $this->orderedItems->count() === 1
                            ? __('1 producte')
                            : __(':count productes', ['count' => $this->orderedItems->count()]) }}
                    </span>
                </div>

                <div class="grid gap-2 p-3 sm:grid-cols-2 sm:p-4 xl:grid-cols-3">
                    @foreach ($this->orderedItems as $item)
                        <div
                            wire:key="full-list-item-{{ $item->id }}"
                            data-test="full-list-item"
                            x-data="{ purchased: @js($item->purchased) }"
                            @class([
                                'flex items-center gap-3 rounded-xl border px-3 py-2.5',
                                'border-emerald-200/80 bg-emerald-50/70 dark:border-emerald-900/70 dark:bg-emerald-950/20' => $item->purchased,
                                'border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-700/70 dark:bg-zinc-950/30' => ! $item->purchased,
                            ])
                        >
                            <label class="flex shrink-0 cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-900"
                                    :checked="purchased"
                                    x-on:change="purchased = ! purchased; $wire.togglePurchased({{ $item->id }})"
                                >
                            </label>

                            <span
                                data-test="full-list-shop-badge"
                                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold shadow-sm"
                                style="{{ $this->shopBadgeStyle($item->shop) }}"
                                title="{{ $item->shop->name }}"
                                aria-label="{{ __('Botiga :shop', ['shop' => $item->shop->name]) }}"
                            >
                                {{ $this->shopInitial($item->shop) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                                        {{ $item->quantity }}
                                    </span>

                                    <p @class([
                                        'min-w-0 truncate font-medium leading-tight',
                                        'text-zinc-400 line-through dark:text-zinc-500' => $item->purchased,
                                        'text-zinc-900 dark:text-zinc-50' => ! $item->purchased,
                                    ])>
                                        {{ $item->name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
