<?php

use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Models\UserGroup;
use App\ShoppingListItemQuantityUnit;
use App\ShoppingListItemVisibility;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('authenticated user sees group shops and public items only', function () {
    $group = UserGroup::factory()->create();
    $otherGroup = UserGroup::factory()->create();

    $user = User::factory()->inGroup($group)->create();
    $groupMember = User::factory()->inGroup($group)->create();
    $otherUser = User::factory()->inGroup($otherGroup)->create();

    $ownShop = Shop::factory()->for($user)->create([
        'name' => 'Mercat del barri',
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($ownShop)->for($user)->create([
        'name' => 'Pomes',
        'quantity' => 4,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $groupShop = Shop::factory()->for($groupMember)->create([
        'name' => 'Botiga compartida',
        'user_group_id' => $group->id,
        'position' => 2,
    ]);

    ShoppingListItem::factory()->for($groupShop)->for($groupMember)->create([
        'name' => 'Llet compartida',
        'quantity' => 2,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($groupShop)->for($groupMember)->asPrivate()->create([
        'name' => 'Secret privat',
        'quantity' => 1,
        'position' => 2,
    ]);

    $otherShop = Shop::factory()->for($otherUser)->create([
        'name' => 'Botiga aliena',
        'user_group_id' => $otherGroup->id,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($otherShop)->for($otherUser)->create([
        'name' => 'Producte ocult',
        'quantity' => 2,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Mercat del barri')
        ->assertSee('Botiga compartida')
        ->assertSee('Pomes')
        ->assertSee('Llet compartida')
        ->assertDontSee('Secret privat')
        ->assertDontSee('Botiga aliena')
        ->assertDontSee('Producte ocult');
});

test('shopping list renders compact layout hooks', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'color' => '#c2410c',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pasta',
        'purchased' => false,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('max-w-[90rem]', false)
        ->assertSee('data-test="shopping-list-header-stats"', false)
        ->assertSee('data-test="shopping-list-header-actions"', false)
        ->assertSeeInOrder([
            'data-test="shopping-list-header-stats"',
            'data-test="shopping-list-header-actions"',
        ], false)
        ->assertSee('x-data="{ expanded: false }"', false)
        ->assertDontSee("window.matchMedia('(min-width: 768px)').matches", false)
        ->assertSee('rounded-xl', false)
        ->assertSee('data-shop-shell', false)
        ->assertSee('data-shop-body', false)
        ->assertSee('data-shop-items', false)
        ->assertSee('app-shop-section', false)
        ->assertSee('app-shop-header-pill', false)
        ->assertSee('--shop-header-bg: rgb(194, 65, 12)', false)
        ->assertSee('wire:sort="reorderShops"', false)
        ->assertSee('wire:sort:item="'.$shop->id.'"', false)
        ->assertSee('wire:sort="reorderItems"', false)
        ->assertDontSee('wire:sort="reorderItems('.$shop->id.', $item, $position)"', false)
        ->assertSee('data-test="shop-primary-actions"', false)
        ->assertSee('data-test="add-item-button"', false)
        ->assertSee('data-test="item-edit-button"', false)
        ->assertSee('data-test="item-quantity-text"', false)
        ->assertDontSee('data-test="item-quantity-input"', false)
        ->assertDontSee('app-shop-item-quantity', false)
        ->assertSee('data-test="item-form-modal"', false)
        ->assertDontSee('wire:model="newItemNames.', false)
        ->assertSee('data-test="edit-shop-action"', false)
        ->assertSee('app-shop-icon-button', false)
        ->assertSee('data-test="edit-shop-button"', false)
        ->assertDontSee('data-test="delete-shop-button"', false)
        ->assertDontSee('Grup:', false)
        ->assertSee('aria-label="Edita la botiga"', false);
});

test('shopping list item names can wrap onto multiple lines', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Nom de producte especialment llarg per comprovar que no queda tallat',
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('break-words whitespace-normal font-medium leading-tight', false)
        ->assertDontSee('truncate font-medium leading-tight', false);
});

test('shop header shows the visible pending to total items ratio', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pasta',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Tomàquet',
        'purchased' => true,
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Per comprar')
        ->assertSee('Productes')
        ->assertSee('1/2')
        ->assertSee('pendents')
        ->assertSeeInOrder(['1/2', 'Mercat central']);
});

test('shopping list header emphasizes pending products and pending shops', function () {
    $user = User::factory()->create();

    $firstShop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    $secondShop = Shop::factory()->for($user)->create([
        'name' => 'Forn del barri',
        'position' => 2,
    ]);

    ShoppingListItem::factory()->for($firstShop)->for($user)->create([
        'name' => 'Pasta',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($firstShop)->for($user)->create([
        'name' => 'Tomàquet',
        'purchased' => true,
        'position' => 2,
    ]);

    ShoppingListItem::factory()->for($secondShop)->for($user)->create([
        'name' => 'Pa',
        'purchased' => false,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Per comprar')
        ->assertSee('Productes')
        ->assertSee('2/3')
        ->assertSee('Botigues')
        ->assertSee('2')
        ->assertDontSee('Comprats')
        ->assertDontSee('Botigues totals');
});

test('shopping list ignores products purchased over a week ago in pending totals and purchased visibility', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pasta',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Tomàquet antic',
        'purchased' => true,
        'purchased_at' => now()->subDays(8),
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('1/1')
        ->assertDontSee('Tomàquet antic');

    Livewire::test('pages::shopping-list')
        ->assertDontSee('Tomàquet antic')
        ->call('togglePurchasedVisibility')
        ->assertDontSee('Tomàquet antic');
});

test('shopping list surfaces purchased items as repurchase suggestions before revealing them in the list', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pasta',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Tomàquet',
        'purchased' => true,
        'purchased_at' => now(),
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Mostra comprats')
        ->assertSee('Pasta')
        ->assertSee('Torna a afegir')
        ->assertSee('Tomàquet');

    Livewire::test('pages::shopping-list')
        ->assertSee('Pasta')
        ->assertSee('Tomàquet')
        ->call('togglePurchasedVisibility')
        ->assertSee('Amaga comprats')
        ->assertSee('Pasta')
        ->assertSee('Tomàquet');
});

test('shopping list shows pending items before purchased ones when purchased items are visible', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Comprat primer',
        'purchased' => true,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pendent segon',
        'purchased' => false,
        'position' => 2,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::shopping-list')
        ->call('togglePurchasedVisibility')
        ->assertSeeInOrder(['Pendent segon', 'Comprat primer']);
});

test('shopping list keeps shops visible but muted when all visible items are purchased', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Tomàquet',
        'purchased' => true,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Mercat central')
        ->assertSee('Mostra comprats')
        ->assertSee('data-test="shop-muted"', false)
        ->assertSee('data-test="shop-empty-pending"', false)
        ->assertSee('No hi ha productes pendents en aquesta botiga.')
        ->assertSee('Torna a afegir')
        ->assertSee('Tomàquet');
});

test('user can create a shop shared with their group using the next position', function () {
    $group = UserGroup::factory()->create();
    $user = User::factory()->inGroup($group)->create();
    $groupMember = User::factory()->inGroup($group)->create();

    Shop::factory()->for($groupMember)->create([
        'name' => 'Compartida inicial',
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startCreatingShop')
        ->set('shopName', 'Segona')
        ->set('shopColor', '#16a34a')
        ->call('saveShop');

    $response->assertHasNoErrors();

    $shop = $user->shops()->where('name', 'Segona')->first();

    expect($shop)->not->toBeNull();
    expect($shop?->position)->toBe(2);
    expect($shop?->user_group_id)->toBe($group->id);
    expect($shop?->color)->toBe('#16a34a');
});

test('master users can not create shops from the shopping list component', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master);

    Livewire::test('pages::shopping-list')
        ->call('startCreatingShop')
        ->assertForbidden();
});

test('user can rename a shop and update its header color', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Botiga antiga',
        'color' => '#d6d3d1',
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startEditingShop', $shop->id)
        ->set('shopName', 'Botiga nova')
        ->set('shopColor', '#0f766e')
        ->call('saveShop');

    $response->assertHasNoErrors();

    expect($shop->refresh()->name)->toBe('Botiga nova');
    expect($shop->refresh()->color)->toBe('#0f766e');
});

test('user can delete a shop and its items', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($user)->create([
        'purchased' => true,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('confirmDeletingShop', $shop->id)
        ->call('deleteShop');

    $response->assertHasNoErrors();

    expect($shop->fresh())->toBeNull();
    expect($item->fresh())->toBeNull();
});

test('user sees the delete action disabled in the edit modal for a shop with pending items', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pasta',
        'purchased' => false,
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::shopping-list')
        ->call('startEditingShop', $shop->id)
        ->assertSee('data-test="delete-shop-action"', false)
        ->assertSee('data-test="delete-shop-button"', false)
        ->assertSee('data-test="delete-shop-disabled-icon"', false)
        ->assertSee('Primer has de marcar tots els productes com a comprats.', false);
});

test('user can not delete a shop with pending items', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($user)->create([
        'purchased' => false,
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::shopping-list')
        ->set('deletingShopId', $shop->id)
        ->call('deleteShop')
        ->assertForbidden();

    expect($shop->fresh())->not->toBeNull();
    expect($item->fresh())->not->toBeNull();
});

test('user can add a private item with quantity and next position', function () {
    $group = UserGroup::factory()->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($user)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Llet',
        'quantity' => 1,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startAddingItem', $shop->id)
        ->set('newItemName', 'Pa')
        ->set('newItemQuantity', 3)
        ->set('newItemVisibility', ShoppingListItemVisibility::Private->value)
        ->call('addItem');

    $response->assertHasNoErrors();

    $item = $shop->shoppingListItems()->where('name', 'Pa')->first();

    expect($item)->not->toBeNull();
    expect((float) $item?->quantity)->toBe(3.0);
    expect($item?->quantity_unit)->toBe(ShoppingListItemQuantityUnit::Unit);
    expect($item?->position)->toBe(2);
    expect($item?->visibility)->toBe(ShoppingListItemVisibility::Private);
    expect($item?->user_id)->toBe($user->id);
});

test('user can add an item in kilograms with a decimal quantity', function () {
    $group = UserGroup::factory()->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($user)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startAddingItem', $shop->id)
        ->set('newItemName', 'Tomàquet de penjar')
        ->set('newItemQuantityUnit', ShoppingListItemQuantityUnit::Kilogram->value)
        ->set('newItemQuantity', '1.25')
        ->set('newItemVisibility', ShoppingListItemVisibility::Public->value)
        ->call('addItem');

    $response->assertHasNoErrors();

    $item = $shop->shoppingListItems()->where('name', 'Tomàquet de penjar')->first();

    expect($item)->not->toBeNull();
    expect((float) $item?->quantity)->toBe(1.25);
    expect($item?->quantity_unit)->toBe(ShoppingListItemQuantityUnit::Kilogram);
    expect($item?->formattedQuantity())->toBe('1,25 kg');
});

test('new item modal uses the selected shop header color', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'color' => '#c2410c',
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::shopping-list')
        ->call('startAddingItem', $shop->id)
        ->assertSet('addingItemShopId', $shop->id)
        ->assertSee('data-test="item-form-header"', false)
        ->assertSee('data-test="item-form-shop-pill"', false)
        ->assertSee('app-shop-pill', false)
        ->assertSee('Mercat central')
        ->assertDontSee('Nou producte')
        ->assertSee('--shop-header-bg: rgb(194, 65, 12)', false);
});

test('group member can reorder visible shops with drag and drop', function () {
    $group = UserGroup::factory()->create();
    $owner = User::factory()->inGroup($group)->create();
    $user = User::factory()->inGroup($group)->create();

    $firstShop = Shop::factory()->for($owner)->create([
        'name' => 'Primera',
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $secondShop = Shop::factory()->for($user)->create([
        'name' => 'Segona',
        'user_group_id' => $group->id,
        'position' => 2,
    ]);

    $thirdShop = Shop::factory()->for($owner)->create([
        'name' => 'Tercera',
        'user_group_id' => $group->id,
        'position' => 3,
    ]);

    ShoppingListItem::factory()->for($firstShop)->for($owner)->create([
        'name' => 'Pomes',
        'purchased' => false,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($secondShop)->for($user)->create([
        'name' => 'Pa',
        'purchased' => false,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($thirdShop)->for($owner)->create([
        'name' => 'Llet',
        'purchased' => false,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('reorderShops', $thirdShop->id, 0);

    $response->assertHasNoErrors();

    expect($thirdShop->refresh()->position)->toBe(1);
    expect($firstShop->refresh()->position)->toBe(2);
    expect($secondShop->refresh()->position)->toBe(3);
});

test('group member can reorder visible public items without touching hidden private positions', function () {
    $group = UserGroup::factory()->create();
    $owner = User::factory()->inGroup($group)->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($owner)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $firstPublicItem = ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'name' => 'Pomes',
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $hiddenPrivateItem = ShoppingListItem::factory()->for($shop)->for($owner)->asPrivate()->create([
        'name' => 'Secret',
        'position' => 2,
    ]);

    $secondPublicItem = ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'name' => 'Llet',
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 3,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('reorderItems', $secondPublicItem->id, 0);

    $response->assertHasNoErrors();

    expect($secondPublicItem->refresh()->position)->toBe(1);
    expect($hiddenPrivateItem->refresh()->position)->toBe(2);
    expect($firstPublicItem->refresh()->position)->toBe(3);
});

test('reordering items ignores empty sort payloads', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($user)->create([
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('reorderItems', null, 0);

    $response->assertHasNoErrors();

    expect($item->refresh()->position)->toBe(1);
});

test('group member can edit a public item name and quantity from the modal', function () {
    $group = UserGroup::factory()->create();
    $owner = User::factory()->inGroup($group)->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($owner)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'quantity' => 1,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startEditingItem', $item->id)
        ->assertSet('editingItemId', $item->id)
        ->assertSet('addingItemShopId', $shop->id)
        ->assertSet('newItemName', $item->name)
        ->assertSet('newItemQuantity', '1')
        ->assertSet('newItemQuantityUnit', ShoppingListItemQuantityUnit::Unit->value)
        ->set('newItemName', 'Farina integral')
        ->set('newItemQuantity', 5)
        ->call('saveItem');

    $response->assertHasNoErrors();

    expect($item->refresh()->name)->toBe('Farina integral');
    expect((float) $item->refresh()->quantity)->toBe(5.0);
});

test('user can soft delete an item from the shopping list', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Formatge',
        'purchased' => false,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startEditingItem', $item->id)
        ->assertSee('data-test="delete-item-button"', false)
        ->call('confirmDeletingItem', $item->id)
        ->call('deleteItem');

    $response->assertHasNoErrors();

    $this->assertSoftDeleted('shopping_list_items', [
        'id' => $item->id,
    ]);

    expect(ShoppingListItem::query()->find($item->id))->toBeNull();
    expect(ShoppingListItem::withTrashed()->find($item->id)?->deleted_at)->not->toBeNull();

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Mercat central')
        ->assertDontSee('Formatge');
});

test('group member can edit a public item to use kilograms from the modal', function () {
    $group = UserGroup::factory()->create();
    $owner = User::factory()->inGroup($group)->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($owner)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'name' => 'Raïm',
        'quantity' => 1,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::shopping-list')
        ->call('startEditingItem', $item->id)
        ->assertSet('newItemQuantity', '1')
        ->assertSet('newItemQuantityUnit', ShoppingListItemQuantityUnit::Unit->value)
        ->set('newItemQuantityUnit', ShoppingListItemQuantityUnit::Kilogram->value)
        ->set('newItemQuantity', '0.75')
        ->call('saveItem');

    $response->assertHasNoErrors();

    expect((float) $item->refresh()->quantity)->toBe(0.75);
    expect($item->refresh()->quantity_unit)->toBe(ShoppingListItemQuantityUnit::Kilogram);
});

test('group member can toggle a public item as purchased', function () {
    $group = UserGroup::factory()->create();
    $owner = User::factory()->inGroup($group)->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($owner)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'purchased' => false,
        'visibility' => ShoppingListItemVisibility::Public,
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::shopping-list')
        ->call('togglePurchased', $item->id);

    expect($item->refresh()->purchased)->toBeTrue();
    expect($item->refresh()->purchased_at)->not->toBeNull();

    Livewire::test('pages::shopping-list')
        ->call('togglePurchased', $item->id);

    expect($item->refresh()->purchased)->toBeFalse();
    expect($item->refresh()->purchased_at)->toBeNull();
});

test('shopping list shows repurchase suggestions for visible purchased items only', function () {
    $group = UserGroup::factory()->create();
    $user = User::factory()->inGroup($group)->create();
    $groupMember = User::factory()->inGroup($group)->create();

    $shop = Shop::factory()->for($groupMember)->create([
        'name' => 'Mercat central',
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($groupMember)->create([
        'name' => 'Llet',
        'quantity' => 2,
        'visibility' => ShoppingListItemVisibility::Public,
        'purchased' => true,
        'purchased_at' => now()->subHour(),
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($groupMember)->asPrivate()->create([
        'name' => 'Secret privat',
        'quantity' => 1,
        'purchased' => true,
        'purchased_at' => now(),
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Torna a afegir')
        ->assertSee('Llet')
        ->assertDontSee('Secret privat');
});

test('group member can repurchase a visible public item and keep its data', function () {
    $group = UserGroup::factory()->create();
    $owner = User::factory()->inGroup($group)->create();
    $user = User::factory()->inGroup($group)->create();
    $shop = Shop::factory()->for($owner)->create([
        'name' => 'Mercat central',
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $originalItem = ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'name' => 'Farina',
        'quantity' => 3,
        'visibility' => ShoppingListItemVisibility::Public,
        'purchased' => true,
        'purchased_at' => now()->subHour(),
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($owner)->create([
        'name' => 'Pa',
        'quantity' => 1,
        'visibility' => ShoppingListItemVisibility::Public,
        'purchased' => false,
        'position' => 2,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::shopping-list')
        ->call('repurchaseItem', $originalItem->id)
        ->assertHasNoErrors();

    $repurchasedItem = $shop->shoppingListItems()
        ->where('name', 'Farina')
        ->where('user_id', $user->id)
        ->latest('id')
        ->first();

    expect($repurchasedItem)->not->toBeNull();
    expect((float) $repurchasedItem?->quantity)->toBe(3.0);
    expect($repurchasedItem?->visibility)->toBe(ShoppingListItemVisibility::Public);
    expect($repurchasedItem?->purchased)->toBeFalse();
    expect($repurchasedItem?->purchased_at)->toBeNull();
    expect($repurchasedItem?->position)->toBe(3);
});

test('shopping list shows weighted quantities with kilograms', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->asWeighted()->create([
        'name' => 'Taronges',
        'quantity' => 1.25,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('1,25 kg')
        ->assertSee('Taronges');
});

test('shopping list shows selected units for decimal quantities', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->withQuantityUnit(ShoppingListItemQuantityUnit::Centiliter)->create([
        'name' => 'Brou',
        'quantity' => 33.5,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('33,5 cl')
        ->assertSee('Brou');
});

test('user cannot modify another users shops or private items', function () {
    $group = UserGroup::factory()->create();
    $user = User::factory()->inGroup($group)->create();
    $otherUser = User::factory()->inGroup($group)->create();
    $otherShop = Shop::factory()->for($otherUser)->create([
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    $otherPrivateItem = ShoppingListItem::factory()->for($otherShop)->for($otherUser)->asPrivate()->create([
        'position' => 1,
    ]);

    $otherPublicItem = ShoppingListItem::factory()->for($otherShop)->for($otherUser)->create([
        'position' => 1,
    ]);

    $this->actingAs($user);

    expect(Gate::forUser($user)->denies('update', $otherShop))->toBeTrue();
    expect(Gate::forUser($user)->allows('reorder', $otherShop))->toBeTrue();
    expect(Gate::forUser($user)->denies('update', $otherPrivateItem))->toBeTrue();
    expect(Gate::forUser($user)->allows('update', $otherPublicItem))->toBeTrue();
    expect(Gate::forUser($user)->allows('reorder', $otherPublicItem))->toBeTrue();

    $shopResponse = Livewire::test('pages::shopping-list')
        ->call('startEditingShop', $otherShop->id);

    Livewire::test('pages::shopping-list')
        ->call('togglePurchased', $otherPublicItem->id);

    $shopResponse->assertSet('editingShopId', null);

    expect($otherPublicItem->refresh()->purchased)->toBeTrue();
    expect($otherPrivateItem->refresh()->purchased)->toBeFalse();
});
