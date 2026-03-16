<?php

use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Models\UserGroup;
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
        'purchased' => true,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('max-w-[90rem]', false)
        ->assertSee('x-data="{ expanded: false }"', false)
        ->assertDontSee("window.matchMedia('(min-width: 768px)').matches", false)
        ->assertSee('rounded-xl', false)
        ->assertSee('data-shop-shell', false)
        ->assertSee('data-shop-body', false)
        ->assertSee('data-shop-items', false)
        ->assertSee('app-shop-section', false)
        ->assertSee('--shop-header-bg: rgba(194, 65, 12, 0.46)', false)
        ->assertSee('wire:sort="reorderShops"', false)
        ->assertSee('wire:sort:item="'.$shop->id.'"', false)
        ->assertSee('wire:sort="reorderItems('.$shop->id.', $item, $position)"', false)
        ->assertSee('data-test="shop-primary-actions"', false)
        ->assertSee('data-test="shop-secondary-actions"', false)
        ->assertSee('data-test="add-item-button"', false)
        ->assertSee('data-test="item-form-modal"', false)
        ->assertDontSee('wire:model="newItemNames.', false)
        ->assertSee('data-test="toggle-shop-content-button"', false)
        ->assertSee('data-test="delete-shop-action"', false)
        ->assertSee('data-test="delete-shop-button"', false)
        ->assertSee('data-test="edit-shop-button"', false)
        ->assertDontSee('Grup:', false)
        ->assertSee('aria-label="Eliminar botiga"', false);
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
        ->assertSeeInOrder(['1/2', 'Mercat central'])
        ->assertDontSee('producte pendent');
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

test('user sees the delete action disabled for a shop with pending items', function () {
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

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="delete-shop-button"', false)
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
    expect($item?->quantity)->toBe(3);
    expect($item?->position)->toBe(2);
    expect($item?->visibility)->toBe(ShoppingListItemVisibility::Private);
    expect($item?->user_id)->toBe($user->id);
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
        ->assertSee('Mercat central')
        ->assertSee('--shop-header-bg: rgba(194, 65, 12, 0.46)', false);
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
        ->call('reorderItems', $shop->id, $secondPublicItem->id, 0);

    $response->assertHasNoErrors();

    expect($secondPublicItem->refresh()->position)->toBe(1);
    expect($hiddenPrivateItem->refresh()->position)->toBe(2);
    expect($firstPublicItem->refresh()->position)->toBe(3);
});

test('group member can update a public item quantity', function () {
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
        ->call('updateItemQuantity', $item->id, 5);

    $response->assertHasNoErrors();

    expect($item->refresh()->quantity)->toBe(5);
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
