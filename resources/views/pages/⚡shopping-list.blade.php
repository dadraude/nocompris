<?php

use App\Concerns\ShoppingListValidationRules;
use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\ShoppingListItemVisibility;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Llista de la compra')] class extends Component
{
    use AuthorizesRequests;
    use ShoppingListValidationRules;

    public ?int $editingShopId = null;

    public ?int $deletingShopId = null;

    public string $shopName = '';

    /** @var array<int, string> */
    public array $newItemNames = [];

    /** @var array<int, int> */
    public array $newItemQuantities = [];

    /** @var array<int, string> */
    public array $newItemVisibilities = [];

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
            ->orderBy('position')
            ->get();
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
    }

    /**
     * Persist a new or existing shop.
     */
    public function saveShop(): void
    {
        $validated = Validator::make(
            ['name' => $this->shopName],
            $this->shopDataRules(),
            [],
            ['name' => 'nom de la botiga'],
        )->validate();

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
     * Create a new item for the given shop.
     */
    public function addItem(int $shopId): void
    {
        $shop = $this->findShop($shopId);

        $this->authorize('create', [ShoppingListItem::class, $shop]);

        $validated = Validator::make(
            [
                'name' => $this->newItemNames[$shopId] ?? '',
                'quantity' => $this->newItemQuantities[$shopId] ?? 1,
                'visibility' => $this->newItemVisibilities[$shopId] ?? ShoppingListItemVisibility::Public->value,
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

        $this->newItemNames[$shopId] = '';
        $this->newItemQuantities[$shopId] = 1;
        $this->newItemVisibilities[$shopId] = ShoppingListItemVisibility::Public->value;

        $this->dispatch('item-added', shopId: $shopId);
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
     * Reset the shop form state.
     */
    protected function resetShopForm(): void
    {
        $this->resetValidation();
        $this->editingShopId = null;
        $this->shopName = '';
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
    <div class="mx-auto flex w-full max-w-[90rem] flex-col gap-3 px-2.5 py-3 sm:gap-4 sm:px-4 sm:py-4 lg:px-5 xl:px-6">
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
            <div class="grid gap-3">
                @foreach ($this->shops as $shop)
                    <article
                        wire:key="shop-{{ $shop->id }}"
                        x-data="{ expanded: window.matchMedia('(min-width: 768px)').matches, addingItem: false }"
                        x-on:item-added.window="if ($event.detail.shopId === {{ $shop->id }}) addingItem = false"
                        class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/90 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/70 sm:rounded-2xl"
                    >
                        <div class="flex flex-col gap-2 border-b border-zinc-200/70 px-3 py-3 dark:border-zinc-700/70 sm:flex-row sm:items-center sm:justify-between sm:px-4">
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-center gap-2 text-left"
                                x-on:click="expanded = ! expanded"
                            >
                                <span class="flex size-9 items-center justify-center rounded-xl bg-stone-100 text-sm font-semibold text-stone-700 dark:bg-zinc-800 dark:text-zinc-200 sm:size-10">
                                    {{ str($shop->name)->substr(0, 2)->upper() }}
                                </span>
                                <span class="min-w-0 space-y-0.5">
                                    <flux:heading size="lg" class="truncate">{{ $shop->name }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $shop->shoppingListItems->where('purchased', false)->count() === 1
                                            ? __('1 producte pendent')
                                            : __(':count productes pendents', ['count' => $shop->shoppingListItems->where('purchased', false)->count()]) }}
                                    </flux:text>
                                    <flux:text class="hidden text-[0.7rem] uppercase tracking-[0.16em] text-zinc-400 dark:text-zinc-500 sm:block">
                                        {{ $shop->user_group_id !== null
                                            ? __('Grup: :group', ['group' => $shop->userGroup?->name])
                                            : __('Botiga personal') }}
                                    </flux:text>
                                </span>
                            </button>

                            <div class="flex flex-wrap items-center gap-2">
                                <flux:button variant="ghost" size="sm" x-on:click="addingItem = ! addingItem">
                                    {{ __('Afegir producte') }}
                                </flux:button>

                                @can('update', $shop)
                                    <flux:modal.trigger name="shop-form">
                                        <flux:button variant="subtle" size="sm" wire:click="startEditingShop({{ $shop->id }})">
                                            {{ __('Editar') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endcan

                                @can('delete', $shop)
                                    <flux:modal.trigger name="delete-shop">
                                        <flux:button variant="danger" size="sm" wire:click="confirmDeletingShop({{ $shop->id }})">
                                            {{ __('Eliminar') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endcan
                            </div>
                        </div>

                        <div x-show="expanded">
                            <div class="space-y-2.5 px-3 py-3 sm:px-4">
                                <form
                                    x-show="addingItem"
                                    x-cloak
                                    wire:submit="addItem({{ $shop->id }})"
                                    class="grid gap-2.5 rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-2.5 dark:border-zinc-700/70 dark:bg-zinc-950/40 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_8rem_11rem_auto]"
                                >
                                    <flux:input
                                        wire:model="newItemNames.{{ $shop->id }}"
                                        :label="__('Producte')"
                                        :placeholder="__('Ex. tomàquets')"
                                    />

                                    <flux:input
                                        wire:model="newItemQuantities.{{ $shop->id }}"
                                        :label="__('Qtat.')"
                                        type="number"
                                        min="1"
                                    />

                                    <flux:select
                                        wire:model="newItemVisibilities.{{ $shop->id }}"
                                        :label="__('Visibilitat')"
                                    >
                                        <option value="{{ \App\ShoppingListItemVisibility::Public->value }}">{{ __('Públic del grup') }}</option>
                                        <option value="{{ \App\ShoppingListItemVisibility::Private->value }}">{{ __('Privat') }}</option>
                                    </flux:select>

                                    <div class="flex items-end">
                                        <flux:button variant="primary" type="submit" class="w-full xl:w-auto">
                                            {{ __('Afegir') }}
                                        </flux:button>
                                    </div>
                                </form>

                                @if ($shop->shoppingListItems->isEmpty())
                                    <div class="rounded-xl border border-dashed border-zinc-200 px-3 py-4 text-center dark:border-zinc-700">
                                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                                            {{ __('Encara no hi ha productes en aquesta botiga.') }}
                                        </flux:text>
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($shop->shoppingListItems as $item)
                                            <div
                                                wire:key="item-{{ $item->id }}"
                                                x-data="{ purchased: @js($item->purchased) }"
                                                :class="purchased
                                                    ? 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900/70 dark:bg-emerald-950/30'
                                                    : 'border-zinc-200/70 bg-white dark:border-zinc-700/70 dark:bg-zinc-950/30'"
                                                class="grid grid-cols-[auto_minmax(0,1fr)] gap-2 rounded-xl border p-2.5 sm:grid-cols-[auto_minmax(0,1fr)_7.5rem] sm:items-center"
                                            >
                                                <label class="mt-1 flex cursor-pointer items-start">
                                                    <input
                                                        type="checkbox"
                                                        class="mt-0.5 size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-900"
                                                        :checked="purchased"
                                                        x-on:change="purchased = ! purchased; $wire.togglePurchased({{ $item->id }})"
                                                    >
                                                </label>

                                                <div class="space-y-1">
                                                    <p
                                                        :class="purchased
                                                            ? 'text-zinc-400 line-through dark:text-zinc-500'
                                                            : 'text-zinc-900 dark:text-zinc-50'"
                                                        class="font-medium leading-tight"
                                                    >
                                                        {{ $item->name }}
                                                    </p>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                                        <span>{{ $item->purchased ? __('Comprat') : __('Per comprar') }}</span>
                                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[0.7rem] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                            {{ $item->visibility === \App\ShoppingListItemVisibility::Public ? __('Públic') : __('Privat') }}
                                                        </span>
                                                        <span class="hidden text-[0.7rem] uppercase tracking-[0.16em] text-zinc-400 dark:text-zinc-500 md:inline">
                                                            {{ __('Creat per :name', ['name' => $item->user->name]) }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="col-span-2 sm:col-span-1">
                                                    <flux:input
                                                        :label="__('Qtat.')"
                                                        type="number"
                                                        min="1"
                                                        :value="$item->quantity"
                                                        wire:change="updateItemQuantity({{ $item->id }}, $event.target.value)"
                                                    />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
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

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel·la') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ $editingShopId === null ? __('Crea botiga') : __('Desa canvis') }}
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
