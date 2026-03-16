<?php

use App\Concerns\ShoppingListValidationRules;
use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\ShoppingListItemVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Llista de la compra')] class extends Component
{
    use AuthorizesRequests;
    use ShoppingListValidationRules;

    private const DEFAULT_SHOP_COLOR = '#d6d3d1';

    public ?int $editingShopId = null;

    public ?int $deletingShopId = null;

    public ?int $addingItemShopId = null;

    public ?int $editingItemId = null;

    public string $shopName = '';

    public string $shopColor = self::DEFAULT_SHOP_COLOR;

    public string $newItemName = '';

    public int $newItemQuantity = 1;

    public string $newItemVisibility = 'public';

    /**
     * Get the shops for the authenticated user.
     */
    #[Computed]
    public function shops(): Collection
    {
        $user = Auth::user();

        return Shop::query()
            ->visibleTo($user)
            ->with([
                'userGroup',
                'user',
                'shoppingListItems' => fn ($query) => $query
                    ->visibleTo($user)
                    ->with('user'),
            ])
            ->withCount([
                'shoppingListItems as visible_pending_items_count' => fn ($query) => $query
                    ->visibleTo($user)
                    ->where('purchased', false),
            ])
            ->orderBy('position')
            ->get();
    }

    /**
     * Get the shop currently being edited, if available.
     */
    #[Computed]
    public function editingShop(): ?Shop
    {
        if ($this->editingShopId === null) {
            return null;
        }

        return $this->shops->firstWhere('id', $this->editingShopId);
    }

    /**
     * Build the CSS variables used by the shop header accent.
     */
    public function shopHeaderStyle(Shop $shop): string
    {
        [$red, $green, $blue] = $this->rgbChannelsFromHex($shop->color);

        return implode('; ', [
            "--shop-header-bg: rgb({$red}, {$green}, {$blue})",
            "--shop-dark-header-bg: rgb({$red}, {$green}, {$blue})",
        ]).';';
    }

    /**
     * Prepare the modal to create a new shop.
     */
    public function startCreatingShop(): void
    {
        $this->authorize('create', Shop::class);

        $this->resetShopForm();
    }

    /**
     * Prepare the modal to update an existing shop.
     */
    public function startEditingShop(int $shopId): void
    {
        $shop = $this->findShop($shopId);

        $this->authorize('update', $shop);

        $this->resetValidation();

        $this->editingShopId = $shop->id;
        $this->shopName = $shop->name;
        $this->shopColor = $this->normalizeShopColor($shop->color);
    }

    /**
     * Persist a new or existing shop.
     */
    public function saveShop(): void
    {
        $validated = Validator::make(
            ['name' => $this->shopName, 'color' => $this->shopColor],
            $this->shopDataRules(),
            [],
            ['name' => 'nom de la botiga', 'color' => 'color de la capçalera'],
        )->validate();

        $validated['color'] = $this->normalizeShopColor($validated['color']);

        if ($this->editingShopId !== null) {
            $shop = $this->findShop($this->editingShopId);

            $this->authorize('update', $shop);

            $shop->update($validated);
        } else {
            $this->authorize('create', Shop::class);

            Auth::user()->shops()->create([
                ...$validated,
                'user_group_id' => Auth::user()->user_group_id,
                'position' => $this->nextShopPosition(),
            ]);
        }

        $this->resetShopForm();
        $this->modal('shop-form')->close();
        $this->dispatch('shop-saved');
    }

    /**
     * Prepare the confirmation modal to delete a shop.
     */
    public function confirmDeletingShop(int $shopId): void
    {
        $shop = $this->findShop($shopId);

        $this->authorize('delete', $shop);

        $this->deletingShopId = $shop->id;
    }

    /**
     * Delete the selected shop.
     */
    public function deleteShop(): void
    {
        $shop = $this->findShop((int) $this->deletingShopId);

        $this->authorize('delete', $shop);

        $shop->delete();

        $this->deletingShopId = null;
        $this->modal('delete-shop')->close();
        $this->dispatch('shop-deleted');
    }

    /**
     * Prepare the modal to create a new item for the selected shop.
     */
    public function startAddingItem(int $shopId): void
    {
        $shop = $this->findShop($shopId);

        $this->authorize('create', [ShoppingListItem::class, $shop]);

        $this->resetItemForm();
        $this->addingItemShopId = $shop->id;
    }

    /**
     * Prepare the modal to update an existing item.
     */
    public function startEditingItem(int $itemId): void
    {
        $item = $this->findItem($itemId);

        $this->authorize('update', $item);

        $this->resetItemForm();
        $this->editingItemId = $item->id;
        $this->addingItemShopId = $item->shop_id;
        $this->newItemName = $item->name;
        $this->newItemQuantity = $item->quantity;
        $this->newItemVisibility = $item->visibility->value;
    }

    /**
     * Persist a new or existing item.
     */
    public function saveItem(): void
    {
        if ($this->editingItemId !== null) {
            $item = $this->findItem($this->editingItemId);

            $this->authorize('update', $item);

            $validated = Validator::make(
                [
                    'name' => $this->newItemName,
                    'quantity' => $this->newItemQuantity,
                ],
                [
                    'name' => $this->itemNameRules(),
                    'quantity' => $this->quantityRules(),
                ],
                [],
                ['name' => 'producte', 'quantity' => 'quantitat'],
            )->validate();

            $item->update($validated);

            $this->resetItemForm();
            $this->modal('item-form')->close();
            $this->dispatch('item-updated');

            return;
        }

        $shop = $this->findShop((int) $this->addingItemShopId);

        $this->authorize('create', [ShoppingListItem::class, $shop]);

        $validated = Validator::make(
            [
                'name' => $this->newItemName,
                'quantity' => $this->newItemQuantity,
                'visibility' => $this->newItemVisibility,
            ],
            $this->shoppingListItemDataRules(),
            [],
            ['name' => 'producte', 'quantity' => 'quantitat', 'visibility' => 'visibilitat'],
        )->validate();

        $shop->shoppingListItems()->create([
            ...$validated,
            'user_id' => Auth::id(),
            'position' => $this->nextItemPosition($shop),
        ]);

        $this->resetItemForm();
        $this->modal('item-form')->close();
        $this->dispatch('item-added', shopId: $shop->id);
    }

    /**
     * Create a new item for the selected shop.
     */
    public function addItem(): void
    {
        $this->saveItem();
    }

    /**
     * Update the quantity for an existing item.
     */
    public function updateItemQuantity(int $itemId, mixed $quantity): void
    {
        $item = $this->findItem($itemId);

        $this->authorize('update', $item);

        $validated = Validator::make(
            ['quantity' => $quantity],
            ['quantity' => $this->quantityRules()],
            [],
            ['quantity' => 'quantitat'],
        )->validate();

        $item->update($validated);
        $this->dispatch('item-updated');
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
     * Persist a new order for the visible shops.
     */
    public function reorderShops(int $shopId, int $position): void
    {
        $shop = $this->findShop($shopId);

        $this->authorize('reorder', $shop);

        $orderedShops = $this->moveModelsToPosition($this->shops, $shopId, $position);

        $this->persistPositions(
            $orderedShops,
            range(1, $orderedShops->count()),
        );
    }

    /**
     * Persist a new order for the visible items in a shop.
     */
    public function reorderItems(?int $itemId, ?int $position): void
    {
        if ($itemId === null || $position === null) {
            return;
        }

        $item = $this->findItem($itemId);
        $shop = $this->findShop($item->shop_id);
        $visibleItems = $shop->shoppingListItems()
            ->visibleTo(Auth::user())
            ->with('user')
            ->get();
        $item = $visibleItems->firstWhere('id', $itemId) ?? $item;

        $this->authorize('reorder', $item);

        $orderedItems = $this->moveModelsToPosition($visibleItems, $itemId, $position);

        $this->persistPositions(
            $orderedItems,
            $visibleItems->pluck('position')->sort()->values()->all(),
        );
    }

    /**
     * Get the next position for a new shop.
     */
    protected function nextShopPosition(): int
    {
        return (int) Shop::query()->visibleTo(Auth::user())->max('position') + 1;
    }

    /**
     * Get the next position for a new item.
     */
    protected function nextItemPosition(Shop $shop): int
    {
        return (int) $shop->shoppingListItems()->max('position') + 1;
    }

    /**
     * Move a model to the requested zero-based index.
     */
    protected function moveModelsToPosition(Collection $models, int $modelId, int $position): Collection
    {
        $orderedModels = $models->values();
        $currentIndex = $orderedModels->search(
            fn (Model $model): bool => $model->getKey() === $modelId,
        );

        if ($currentIndex === false) {
            abort(404);
        }

        $targetIndex = max(0, min($position, $orderedModels->count() - 1));

        if ($currentIndex === $targetIndex) {
            return $orderedModels;
        }

        $movedModel = $orderedModels->pull($currentIndex);

        $orderedModels->splice($targetIndex, 0, [$movedModel]);

        return $orderedModels->values();
    }

    /**
     * Persist the supplied positions for the given models.
     *
     * @param  array<int, int>  $positions
     */
    protected function persistPositions(Collection $models, array $positions): void
    {
        $models->each(function (Model $model, int $index) use ($positions): void {
            $position = $positions[$index] ?? $index + 1;

            if ((int) $model->position === $position) {
                return;
            }

            $model->update(['position' => $position]);
        });
    }

    /**
     * Reset the shop form state.
     */
    protected function resetShopForm(): void
    {
        $this->resetValidation();
        $this->editingShopId = null;
        $this->shopName = '';
        $this->shopColor = self::DEFAULT_SHOP_COLOR;
    }

    /**
     * Reset the item form state.
     */
    protected function resetItemForm(): void
    {
        $this->resetValidation();
        $this->editingItemId = null;
        $this->addingItemShopId = null;
        $this->newItemName = '';
        $this->newItemQuantity = 1;
        $this->newItemVisibility = ShoppingListItemVisibility::Public->value;
    }

    /**
     * Normalize the configured shop header color.
     */
    protected function normalizeShopColor(?string $color): string
    {
        $normalizedColor = '#'.Str::lower(ltrim((string) $color, '#'));

        if (! preg_match('/^#[0-9a-f]{6}$/', $normalizedColor)) {
            return self::DEFAULT_SHOP_COLOR;
        }

        return $normalizedColor;
    }

    /**
     * Convert a hex color into decimal RGB channels.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected function rgbChannelsFromHex(?string $color): array
    {
        $normalizedColor = ltrim($this->normalizeShopColor($color), '#');

        return [
            hexdec(substr($normalizedColor, 0, 2)),
            hexdec(substr($normalizedColor, 2, 2)),
            hexdec(substr($normalizedColor, 4, 2)),
        ];
    }

    /**
     * Find a shop by its identifier.
     */
    protected function findShop(int $shopId): Shop
    {
        return Shop::query()
            ->with(['user', 'userGroup'])
            ->visibleTo(Auth::user())
            ->findOrFail($shopId);
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
            <div class="flex flex-col gap-2 px-3 py-3 sm:gap-3 sm:px-4 sm:py-4 lg:gap-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="hidden max-w-2xl space-y-1 sm:block">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Organitza la compra</p>
                        <flux:heading size="xl" level="1">{{ __('Llista de la compra') }}</flux:heading>
                        <flux:subheading class="max-w-2xl text-sm">
                            {{ __('Comparteix botigues amb el teu grup i decideix si cada producte és públic o privat.') }}
                        </flux:subheading>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end lg:justify-end">
                        <div class="hidden items-center gap-2 xl:flex">
                            <x-action-message on="shop-saved">{{ __('Botiga desada.') }}</x-action-message>
                            <x-action-message on="shop-deleted">{{ __('Botiga eliminada.') }}</x-action-message>
                            <x-action-message on="item-added">{{ __('Producte afegit.') }}</x-action-message>
                            <x-action-message on="item-updated">{{ __('Quantitat actualitzada.') }}</x-action-message>
                        </div>

                        <flux:modal.trigger name="shop-form" class="w-full sm:w-auto">
                            <flux:button variant="primary" size="sm" wire:click="startCreatingShop" class="w-full sm:w-auto">
                                {{ __('Nova botiga') }}
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-1.5 rounded-[1.25rem] border border-zinc-200/70 bg-white/80 p-2.5 backdrop-blur-sm dark:border-zinc-700/70 dark:bg-zinc-950/40">
                    <div class="rounded-xl bg-zinc-50 px-2.5 py-2 dark:bg-zinc-900/80">
                        <p class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ __('Botigues') }}</p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->shops->count() }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 px-2.5 py-2 dark:bg-zinc-900/80">
                        <p class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ __('Pendents') }}</p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->shops->sum(fn ($shop) => $shop->shoppingListItems->where('purchased', false)->count()) }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 px-2.5 py-2 dark:bg-zinc-900/80">
                        <p class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ __('Comprats') }}</p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->shops->sum(fn ($shop) => $shop->shoppingListItems->where('purchased', true)->count()) }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($this->shops->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white/80 px-4 py-6 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60 sm:rounded-2xl">
                <flux:heading size="lg">{{ __('Encara no tens cap botiga') }}</flux:heading>
                <flux:text class="mt-3 text-zinc-500 dark:text-zinc-400">
                    {{ __('Crea la primera botiga per començar a preparar la teva compra setmanal.') }}
                </flux:text>
            </div>
        @else
            <div class="grid gap-3" wire:sort="reorderShops">
                @foreach ($this->shops as $shop)
                    <article
                        wire:key="shop-{{ $shop->id }}"
                        wire:sort:item="{{ $shop->id }}"
                        x-data="{ expanded: false }"
                        data-shop-shell
                        class="app-shop-card"
                        style="{{ $this->shopHeaderStyle($shop) }}"
                    >
                        <div class="app-shop-header relative">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                @can('reorder', $shop)
                                    <button
                                        type="button"
                                        wire:sort:handle
                                        class="inline-flex size-9 shrink-0 cursor-grab items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-400 transition hover:border-zinc-300 hover:text-zinc-600 active:cursor-grabbing dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-500 dark:hover:border-zinc-600 dark:hover:text-zinc-300"
                                        aria-label="{{ __('Reordena la botiga') }}"
                                    >
                                        <flux:icon.bars-2 class="size-4" />
                                    </button>
                                @endcan

                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-center gap-2 pe-10 text-left sm:gap-3"
                                    x-on:click="expanded = ! expanded"
                                >
                                    <span class="flex min-w-0 flex-1 items-center">
                                        <span class="app-shop-pill app-shop-header-pill">
                                            <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-sm font-semibold text-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-100">
                                                {{ $shop->visible_pending_items_count }}/{{ $shop->shoppingListItems->count() }}
                                            </span>
                                            <span class="min-w-0 truncate text-lg font-semibold leading-tight text-zinc-950 dark:text-zinc-50 sm:text-xl">
                                                {{ $shop->name }}
                                            </span>
                                        </span>
                                    </span>
                                </button>
                            </div>

                            <div class="absolute right-3 top-3 sm:right-4 sm:top-4">
                                @can('update', $shop)
                                    <div data-test="edit-shop-action">
                                        <flux:modal.trigger name="shop-form">
                                            <flux:button
                                                variant="outline"
                                                size="sm"
                                                icon="pencil-square"
                                                class="app-shop-icon-button"
                                                aria-label="{{ __('Edita la botiga') }}"
                                                data-test="edit-shop-button"
                                                wire:click="startEditingShop({{ $shop->id }})"
                                            ></flux:button>
                                        </flux:modal.trigger>
                                    </div>
                                @endcan
                            </div>
                        </div>

                        <div x-show="expanded">
                            <div class="bg-zinc-50/45 px-3 py-3 dark:bg-zinc-950/20 sm:px-4 sm:pb-4">
                                <div data-shop-body class="app-shop-section space-y-2.5">
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200/70 pb-2 dark:border-zinc-700/70 sm:flex-nowrap"
                                        data-test="shop-primary-actions"
                                    >
                                        <span class="app-shop-pill shrink-0">
                                            {{ $shop->shoppingListItems->count() === 1
                                                ? __('1 producte')
                                                : __(':count productes', ['count' => $shop->shoppingListItems->count()]) }}
                                        </span>
                                        @can('create', [\App\Models\ShoppingListItem::class, $shop])
                                            <flux:modal.trigger name="item-form">
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="shrink-0"
                                                    data-test="add-item-button"
                                                    wire:click="startAddingItem({{ $shop->id }})"
                                                >
                                                    {{ __('Afegir producte') }}
                                                </flux:button>
                                            </flux:modal.trigger>
                                        @endcan
                                    </div>

                                    @if ($shop->shoppingListItems->isEmpty())
                                        <div class="rounded-xl border border-dashed border-zinc-200 bg-white/70 px-3 py-4 text-center dark:border-zinc-700 dark:bg-zinc-950/25">
                                            <flux:text class="text-zinc-500 dark:text-zinc-400">
                                                {{ __('Encara no hi ha productes en aquesta botiga.') }}
                                            </flux:text>
                                        </div>
                                    @else
                                        <div data-shop-items class="space-y-1.5" wire:sort="reorderItems">
                                            @foreach ($shop->shoppingListItems as $item)
                                                <div
                                                    wire:key="item-{{ $item->id }}"
                                                    wire:sort:item="{{ $item->id }}"
                                                    x-data="{ purchased: @js($item->purchased) }"
                                                    :class="purchased
                                                        ? 'border-emerald-200/80 bg-emerald-50/70 dark:border-emerald-900/70 dark:bg-emerald-950/20'
                                                        : 'border-zinc-200/80 bg-white/75 dark:border-zinc-700/70 dark:bg-zinc-950/25'"
                                                    class="app-shop-item flex gap-2.5"
                                                >
                                                    @can('reorder', $item)
                                                        <button
                                                            type="button"
                                                            wire:sort:handle
                                                            class="inline-flex size-8 shrink-0 cursor-grab items-center justify-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 active:cursor-grabbing dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                                                            aria-label="{{ __('Reordena el producte') }}"
                                                        >
                                                            <flux:icon.bars-2 class="size-4" />
                                                        </button>
                                                    @else
                                                        <span class="size-8 shrink-0"></span>
                                                    @endif

                                                    <label class="flex cursor-pointer items-center">
                                                        <input
                                                            type="checkbox"
                                                            class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-900"
                                                            :checked="purchased"
                                                            x-on:change="purchased = ! purchased; $wire.togglePurchased({{ $item->id }})"
                                                        >
                                                    </label>

                                                    <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                                        @can('update', $item)
                                                            <flux:modal.trigger name="item-form" class="min-w-0 flex-1">
                                                                <button
                                                                    type="button"
                                                                    class="flex min-w-0 flex-1 items-center gap-2.5 rounded-lg px-1 py-1 text-left transition hover:bg-zinc-100/75 dark:hover:bg-zinc-800/50"
                                                                    data-test="item-edit-button"
                                                                    aria-label="{{ __('Edita el producte :item', ['item' => $item->name]) }}"
                                                                    wire:click="startEditingItem({{ $item->id }})"
                                                                >
                                                                    <span
                                                                        data-test="item-quantity-text"
                                                                        class="shrink-0 text-sm font-semibold text-zinc-500 dark:text-zinc-400"
                                                                    >
                                                                        {{ $item->quantity }}
                                                                    </span>

                                                                    <p
                                                                        :class="purchased
                                                                            ? 'text-zinc-400 line-through dark:text-zinc-500'
                                                                            : 'text-zinc-900 dark:text-zinc-50'"
                                                                        class="min-w-0 flex-1 truncate font-medium leading-tight"
                                                                    >
                                                                        {{ $item->name }}
                                                                    </p>
                                                                </button>
                                                            </flux:modal.trigger>
                                                        @else
                                                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                                                <span
                                                                    data-test="item-quantity-text"
                                                                    class="shrink-0 text-sm font-semibold text-zinc-500 dark:text-zinc-400"
                                                                >
                                                                    {{ $item->quantity }}
                                                                </span>

                                                                <p
                                                                    :class="purchased
                                                                        ? 'text-zinc-400 line-through dark:text-zinc-500'
                                                                        : 'text-zinc-900 dark:text-zinc-50'"
                                                                    class="min-w-0 flex-1 truncate font-medium leading-tight"
                                                                >
                                                                    {{ $item->name }}
                                                                </p>
                                                            </div>
                                                        @endcan

                                                        <span class="hidden rounded-full bg-zinc-100 px-2 py-0.5 text-[0.68rem] font-medium uppercase tracking-[0.14em] text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300 sm:inline-flex">
                                                            {{ $item->visibility === \App\ShoppingListItemVisibility::Public ? __('Públic') : __('Privat') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>

                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <flux:modal name="shop-form" class="max-w-lg">
        <form wire:submit="saveShop" class="space-y-4">
            <div class="space-y-2">
                <flux:heading size="lg">
                    {{ $editingShopId === null ? __('Nova botiga') : __('Edita la botiga') }}
                </flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Fes servir noms curts i clars per trobar ràpidament cada llista.') }}
                </flux:text>
            </div>

            <flux:input wire:model="shopName" :label="__('Nom')" :placeholder="__('Ex. Mercat central')" />

            <div class="grid gap-2">
                <label for="shop-color" class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                    {{ __('Color de la capçalera') }}
                </label>

                <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-950/45 sm:flex-row sm:items-center">
                    <input
                        id="shop-color"
                        wire:model.live="shopColor"
                        type="color"
                        class="h-11 w-full cursor-pointer rounded-lg border border-zinc-200 bg-white p-1 sm:w-16 dark:border-zinc-700 dark:bg-zinc-900"
                    >

                    <div class="min-w-0 flex-1 space-y-1">
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ \Illuminate\Support\Str::upper($shopColor) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Aquest color només s’aplicarà a la capçalera de la botiga.') }}
                        </p>
                    </div>

                    <div
                        class="h-11 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 sm:w-32"
                        style="background-color: {{ $shopColor }};"
                    ></div>
                </div>

                @error('color')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                @if ($editingShopId !== null && $this->editingShop?->user_id === Auth::id())
                    <div data-test="delete-shop-action">
                        @if (Auth::user()->can('delete', $this->editingShop))
                            <flux:modal.trigger name="delete-shop">
                                <flux:button
                                    variant="danger"
                                    icon="trash"
                                    data-test="delete-shop-button"
                                    wire:click="confirmDeletingShop({{ $this->editingShop->id }})"
                                >
                                    {{ __('Eliminar botiga') }}
                                </flux:button>
                            </flux:modal.trigger>
                        @else
                            <flux:button
                                variant="danger"
                                data-test="delete-shop-button"
                                title="{{ __('Primer has de marcar tots els productes com a comprats.') }}"
                                disabled
                            >
                                <span
                                    data-test="delete-shop-disabled-icon"
                                    aria-hidden="true"
                                    class="relative inline-flex size-4 items-center justify-center"
                                >
                                    <flux:icon.trash class="size-4" />
                                    <flux:icon.slash class="absolute inset-0 m-auto size-4 stroke-[2.25]" />
                                </span>

                                <span>{{ __('Eliminar botiga') }}</span>
                            </flux:button>
                        @endif
                    </div>
                @else
                    <span></span>
                @endif

                <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel·la') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ $editingShopId === null ? __('Crea botiga') : __('Desa canvis') }}
                </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="item-form" class="max-w-lg">
        <form wire:submit="saveItem" class="space-y-4" data-test="item-form-modal">
            @php($itemShop = $this->shops->firstWhere('id', $addingItemShopId))

            <div
                class="app-shop-header me-12 rounded-xl border border-zinc-200/80 pe-12 dark:border-zinc-700/70 sm:me-14 sm:pe-14"
                data-test="item-form-header"
                @if ($itemShop) style="{{ $this->shopHeaderStyle($itemShop) }}" @endif
            >
                <div class="space-y-2">
                    <flux:heading size="lg">
                        {{ $editingItemId === null ? __('Nou producte') : __('Edita el producte') }}
                    </flux:heading>
                    <span
                        data-test="item-form-shop-pill"
                        @class([
                            'app-shop-pill inline-flex max-w-full normal-case tracking-normal text-zinc-800 dark:text-zinc-100',
                            'text-zinc-500 dark:text-zinc-400' => $itemShop === null,
                        ])
                    >
                        <span class="truncate">
                            {{ $itemShop?->name ?? __('Sense seleccionar') }}
                        </span>
                    </span>
                </div>
            </div>

            <flux:input
                wire:model="newItemName"
                :label="__('Producte')"
                :placeholder="__('Ex. tomàquets')"
            />

            <div @class([
                'grid gap-3',
                'sm:grid-cols-[8rem_minmax(0,1fr)]' => $editingItemId === null,
                'sm:max-w-[8rem]' => $editingItemId !== null,
            ])>
                <flux:input
                    wire:model="newItemQuantity"
                    :label="__('Qtat.')"
                    type="number"
                    min="1"
                />

                @if ($editingItemId === null)
                    <flux:select
                        wire:model="newItemVisibility"
                        :label="__('Visibilitat')"
                    >
                        <option value="{{ \App\ShoppingListItemVisibility::Public->value }}">{{ __('Públic del grup') }}</option>
                        <option value="{{ \App\ShoppingListItemVisibility::Private->value }}">{{ __('Privat') }}</option>
                    </flux:select>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel·la') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ $editingItemId === null ? __('Afegir') : __('Desa canvis') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-shop" class="max-w-lg">
        <div class="space-y-4">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Eliminar botiga') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Aquesta acció eliminarà també tots els productes associats a la botiga seleccionada.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel·la') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteShop">
                    {{ __('Eliminar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
