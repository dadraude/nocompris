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

    #[Url(as: 'purchase')]
    public string $purchaseFilter = 'pending';

    /**
     * @var list<int>
     */
    public array $selectedShopIds = [];

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
                    ->relevantForList()
                    ->with('user'),
            ])
            ->orderBy('position')
            ->get();
    }

    /**
     * Get the selected shops for the current filter state.
     */
    #[Computed]
    public function selectedShops(): Collection
    {
        $selectedShopIds = $this->normalizedSelectedShopIds();

        if ($selectedShopIds === []) {
            return new Collection();
        }

        return $this->shops
            ->filter(fn (Shop $shop): bool => in_array($shop->id, $selectedShopIds, true))
            ->values();
    }

    /**
     * Get the shops allowed by the current filter state.
     */
    #[Computed]
    public function filteredShops(): Collection
    {
        if (! $this->hasActiveShopFilters) {
            return $this->shops;
        }

        return $this->selectedShops;
    }

    /**
     * Get every visible item in shop order.
     */
    #[Computed]
    public function items(): SupportCollection
    {
        $items = $this->filteredShops
            ->flatMap(function (Shop $shop): SupportCollection {
                return $shop->shoppingListItems
                    ->map(fn (ShoppingListItem $item): ShoppingListItem => $item->setRelation('shop', $shop));
            })
            ->values();

        if ($this->purchaseFilter === 'pending') {
            return $items
                ->filter(fn (ShoppingListItem $item): bool => ! $item->purchased)
                ->values();
        }

        return $items
            ->sortBy(fn (ShoppingListItem $item): string => sprintf(
                '%d|%010d|%010d',
                $item->purchased ? 1 : 0,
                $item->shop->position,
                $item->position,
            ), SORT_NATURAL)
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
                '%d|%s|%s|%010d',
                $item->purchased ? 1 : 0,
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
     * Determine whether one or more shop filters are active.
     */
    #[Computed]
    public function hasActiveShopFilters(): bool
    {
        return $this->normalizedSelectedShopIds() !== [];
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
     * Keep the purchase filter within the supported values.
     */
    public function updatedPurchaseFilter(string $value): void
    {
        if (! in_array($value, ['all', 'pending'], true)) {
            $this->purchaseFilter = 'all';
        }
    }

    /**
     * Add or remove a shop from the active filter.
     */
    public function toggleShopFilter(int $shopId): void
    {
        $shop = $this->findShop($shopId);

        $this->authorize('view', $shop);

        $selectedShopIds = $this->normalizedSelectedShopIds();

        if (in_array($shopId, $selectedShopIds, true)) {
            $this->selectedShopIds = array_values(array_filter(
                $selectedShopIds,
                fn (int $selectedShopId): bool => $selectedShopId !== $shopId,
            ));

            return;
        }

        $selectedShopIds[] = $shopId;
        $availableShopIds = $this->shops->pluck('id')->all();

        $this->selectedShopIds = array_values(array_filter(
            $availableShopIds,
            fn (int $availableShopId): bool => in_array($availableShopId, $selectedShopIds, true),
        ));
    }

    /**
     * Clear the active shop filters.
     */
    public function clearShopFilters(): void
    {
        $this->selectedShopIds = [];
    }

    /**
     * Determine whether the purchase filter is active.
     */
    #[Computed]
    public function hasActivePurchaseFilter(): bool
    {
        return $this->purchaseFilter === 'pending';
    }

    /**
     * Toggle whether purchased items should be visible.
     */
    public function togglePurchasedVisibility(): void
    {
        $this->purchaseFilter = $this->purchaseFilter === 'pending'
            ? 'all'
            : 'pending';
    }

    /**
     * Toggle the purchased state for an item.
     */
    public function togglePurchased(int $itemId): void
    {
        $item = $this->findItem($itemId);

        $this->authorize('update', $item);

        $item->updatePurchaseState(! $item->purchased);
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
     * Determine whether the given shop is currently selected.
     */
    public function shopFilterIsActive(int $shopId): bool
    {
        return in_array($shopId, $this->normalizedSelectedShopIds(), true);
    }

    /**
     * Determine whether the given purchase filter option is active.
     */
    public function purchaseFilterIsActive(string $value): bool
    {
        return $this->purchaseFilter === $value;
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

    /**
     * Find a visible shop by its identifier.
     */
    protected function findShop(int $shopId): Shop
    {
        return Shop::query()
            ->visibleTo(Auth::user())
            ->findOrFail($shopId);
    }

    /**
     * Normalize the selected shop identifiers.
     *
     * @return list<int>
     */
    protected function normalizedSelectedShopIds(): array
    {
        $availableShopIds = $this->shops
            ->pluck('id')
            ->map(fn (mixed $shopId): int => (int) $shopId)
            ->all();

        return collect($this->selectedShopIds)
            ->map(fn (mixed $shopId): int => (int) $shopId)
            ->filter(fn (int $shopId): bool => $shopId > 0)
            ->filter(fn (int $shopId): bool => in_array($shopId, $availableShopIds, true))
            ->unique()
            ->values()
            ->all();
    }
};
?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-[90rem] flex-col gap-3 px-2.5 pb-3 pt-1.5 sm:gap-4 sm:px-4 sm:pb-4 sm:pt-2 lg:px-5 xl:px-6">
        <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-linear-to-br from-white via-zinc-50 to-stone-100 shadow-sm dark:border-zinc-700/70 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 sm:rounded-2xl">
            <div class="flex flex-col gap-4 px-3 py-3 sm:px-4 sm:py-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <flux:heading size="xl" level="1">{{ __('Llistat complet') }}</flux:heading>
                </div>

                <div class="grid gap-3 lg:min-w-[24rem]">
                    <flux:select wire:model.live="sortMode" :label="__('Organitza per')" data-test="full-list-sort-select">
                        <option value="shop">{{ __('Botiga') }}</option>
                        <option value="alphabetical">{{ __('Ordre alfabètic') }}</option>
                    </flux:select>

                    <div data-test="full-list-purchase-filters">
                        <button
                            type="button"
                            wire:click="togglePurchasedVisibility"
                            data-test="full-list-purchased-toggle"
                            aria-pressed="{{ $this->hasActivePurchaseFilter ? 'false' : 'true' }}"
                            @class([
                                'inline-flex w-fit items-center rounded-full border px-3 py-2 text-sm font-medium transition',
                                'border-zinc-300 bg-white text-zinc-950 shadow-sm dark:border-zinc-500 dark:bg-zinc-800 dark:text-zinc-50' => ! $this->hasActivePurchaseFilter,
                                'border-zinc-200 bg-white/80 text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900/70 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100' => $this->hasActivePurchaseFilter,
                            ])
                        >
                            {{ $this->hasActivePurchaseFilter ? __('Mostra comprats') : __('Amaga comprats') }}
                        </button>
                    </div>

                    @if ($this->shops->isNotEmpty())
                        <div class="grid gap-2" data-test="full-list-shop-filters">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">
                                    {{ __('Filtra per botigues') }}
                                </p>

                                @if ($this->hasActiveShopFilters)
                                    <button
                                        type="button"
                                        wire:click="clearShopFilters"
                                        data-test="full-list-clear-shop-filters"
                                        class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100"
                                    >
                                        {{ __('Mostrar totes') }}
                                    </button>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->shops as $shop)
                                    @php($isSelected = $this->shopFilterIsActive($shop->id))

                                    <button
                                        type="button"
                                        wire:key="full-list-shop-filter-{{ $shop->id }}"
                                        wire:click="toggleShopFilter({{ $shop->id }})"
                                        data-test="full-list-shop-filter"
                                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                        @class([
                                            'inline-flex max-w-full items-center gap-2 rounded-full border px-3 py-2 text-sm font-medium transition',
                                            'border-zinc-300 bg-white text-zinc-950 shadow-sm dark:border-zinc-500 dark:bg-zinc-800 dark:text-zinc-50' => $isSelected,
                                            'border-zinc-200 bg-white/80 text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900/70 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100' => ! $isSelected,
                                        ])
                                    >
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            style="background-color: {{ $shop->color }};"
                                            aria-hidden="true"
                                        ></span>

                                        <span class="truncate">{{ $shop->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($this->orderedItems->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white/80 px-4 py-6 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60 sm:rounded-2xl">
                <flux:heading size="lg">
                    {{ $this->hasActiveShopFilters
                        ? __('No hi ha productes visibles amb els filtres actuals')
                        : ($this->hasActivePurchaseFilter
                            ? __('Encara no tens productes pendents')
                            : __('Encara no tens productes visibles')) }}
                </flux:heading>
                <flux:text class="mt-3 text-zinc-500 dark:text-zinc-400">
                    {{ $this->hasActiveShopFilters
                        ? __('Canvia la selecció de botigues o mostra els comprats per recuperar la vista global.')
                        : ($this->hasActivePurchaseFilter
                            ? __('Quan vulguis revisar també els productes comprats, els pots mostrar amb el botó superior.')
                            : __('Quan afegeixis productes a la llista, els veuràs aquí amb una vista compacta.')) }}
                </flux:text>

                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    @if ($this->hasActiveShopFilters)
                        <button
                            type="button"
                            wire:click="clearShopFilters"
                            class="inline-flex rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950 dark:border-zinc-600 dark:text-zinc-200 dark:hover:border-zinc-500 dark:hover:text-zinc-50"
                        >
                            {{ __('Mostrar totes les botigues') }}
                        </button>
                    @endif

                    @if ($this->hasActivePurchaseFilter)
                        <button
                            type="button"
                            wire:click="togglePurchasedVisibility"
                            class="inline-flex rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950 dark:border-zinc-600 dark:text-zinc-200 dark:hover:border-zinc-500 dark:hover:text-zinc-50"
                        >
                            {{ __('Mostra comprats') }}
                        </button>
                    @endif
                </div>
            </div>
        @else
            <div
                data-test="full-list"
                class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/85 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/65 sm:rounded-2xl"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200/70 px-3 py-3 dark:border-zinc-700/70 sm:px-4">
                    <div class="space-y-1">
                        <flux:heading size="lg">
                            {{ $sortMode === 'alphabetical' ? __('Productes de la A a la Z') : __('Productes segons l’ordre de botiga') }}
                        </flux:heading>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->hasActiveShopFilters
                                ? __('Botigues seleccionades: :count', ['count' => $this->selectedShops->count()])
                                : ($this->hasActivePurchaseFilter
                                    ? __('Mostrant productes pendents')
                                    : __('Mostrant tots els productes visibles')) }}
                        </p>
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
                                        {{ $item->formattedQuantity() }}
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
