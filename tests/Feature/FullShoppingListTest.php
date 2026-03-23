<?php

use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Models\UserGroup;
use App\ShoppingListItemQuantityUnit;
use App\ShoppingListItemVisibility;
use Livewire\Livewire;

test('authenticated user can visit the full shopping list page from the sidebar navigation', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create();
    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'purchased' => false,
    ]);

    $this->actingAs($user);

    expect(route('shopping-list.full', absolute: false))->toBe('/full-shopping-list');

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('Llistat complet')
        ->assertSee('Organitza per')
        ->assertSee('Mostra comprats')
        ->assertSee('Filtra per botigues')
        ->assertSee('data-test="full-list-sort-select"', false)
        ->assertSee('Llista de la compra')
        ->assertDontSee('Vista global');
});

test('full shopping list shows visible items with quantities and shop badges', function () {
    $group = UserGroup::factory()->create();
    $user = User::factory()->inGroup($group)->create();
    $groupMember = User::factory()->inGroup($group)->create();

    $shop = Shop::factory()->for($groupMember)->create([
        'name' => 'Mercat central',
        'color' => '#c2410c',
        'user_group_id' => $group->id,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($groupMember)->create([
        'name' => 'Pasta',
        'quantity' => 7,
        'visibility' => ShoppingListItemVisibility::Public,
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($groupMember)->asPrivate()->create([
        'name' => 'Secret privat',
        'quantity' => 2,
        'position' => 2,
    ]);

    ShoppingListItem::factory()->for($shop)->for($groupMember)->create([
        'name' => 'Ja comprat',
        'quantity' => 1,
        'visibility' => ShoppingListItemVisibility::Public,
        'purchased' => true,
        'position' => 3,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('data-test="full-list"', false)
        ->assertSee('data-test="full-list-item"', false)
        ->assertSee('data-test="full-list-shop-badge"', false)
        ->assertSee('Productes segons l’ordre de botiga')
        ->assertSeeInOrder(['7', 'Pasta'])
        ->assertSee('aria-label="Botiga Mercat central"', false)
        ->assertSee('background-color: rgb(194, 65, 12); color: rgb(250, 250, 250);', false)
        ->assertSee('Mostra comprats')
        ->assertDontSee('Ja comprat')
        ->assertDontSee('Secret privat');
});

test('full shopping list formats weighted quantities with kilograms', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->asWeighted()->create([
        'name' => 'Pebrots',
        'quantity' => 0.75,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('0,75 kg')
        ->assertSee('Pebrots');
});

test('full shopping list formats decimal quantities with their selected unit', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->withQuantityUnit(ShoppingListItemQuantityUnit::Centiliter)->create([
        'name' => 'Caldo',
        'quantity' => 50.5,
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('50,5 cl')
        ->assertSee('Caldo');
});

test('full shopping list uses a flat list ordered by shop position', function () {
    $user = User::factory()->create();

    $firstShop = Shop::factory()->for($user)->create([
        'name' => 'Fruita',
        'position' => 1,
    ]);

    $secondShop = Shop::factory()->for($user)->create([
        'name' => 'Forn',
        'position' => 2,
    ]);

    ShoppingListItem::factory()->for($secondShop)->for($user)->create([
        'name' => 'Pa',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($firstShop)->for($user)->create([
        'name' => 'Poma',
        'position' => 1,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('data-test="full-list"', false)
        ->assertDontSee('data-test="full-list-grouped-by-shop"', false)
        ->assertDontSee('Ordre actual')
        ->assertSeeInOrder(['Poma', 'Pa']);
});

test('full shopping list can be organized alphabetically', function () {
    $user = User::factory()->create();

    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat central',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Tomàquets',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Arròs',
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full', ['sortMode' => 'alphabetical']))
        ->assertSuccessful()
        ->assertSee('data-test="full-list"', false)
        ->assertSee('Productes de la A a la Z')
        ->assertSeeInOrder(['Arròs', 'Tomàquets']);
});

test('full shopping list can toggle items as purchased', function () {
    $user = User::factory()->create();

    $shop = Shop::factory()->for($user)->create([
        'position' => 1,
    ]);

    $item = ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Llet',
        'purchased' => false,
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::full-shopping-list')
        ->assertSee('Llet')
        ->call('togglePurchased', $item->id)
        ->assertDontSee('Llet');

    expect($item->refresh()->purchased)->toBeTrue();
    expect($item->refresh()->purchased_at)->not->toBeNull();
});

test('full shopping list offers shop filters for shops with visible items', function () {
    $user = User::factory()->create();

    $pendingShop = Shop::factory()->for($user)->create([
        'name' => 'Fruita',
        'position' => 1,
    ]);

    $purchasedOnlyShop = Shop::factory()->for($user)->create([
        'name' => 'Rebost',
        'position' => 2,
    ]);

    ShoppingListItem::factory()->for($pendingShop)->for($user)->create([
        'name' => 'Poma',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($purchasedOnlyShop)->for($user)->create([
        'name' => 'Arròs',
        'purchased' => true,
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::full-shopping-list')
        ->assertSee('Fruita')
        ->assertSee('Rebost')
        ->assertSee('Poma')
        ->assertDontSee('Arròs');
});

test('full shopping list can filter items by purchase state', function () {
    $user = User::factory()->create();

    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Formatge pendent',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Iogurt comprat',
        'purchased' => true,
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('Formatge pendent')
        ->assertDontSee('Iogurt comprat');

    $this->get(route('shopping-list.full', ['purchase' => 'all']))
        ->assertSuccessful()
        ->assertSee('Formatge pendent')
        ->assertSee('Iogurt comprat');
});

test('full shopping list ignores products purchased over a week ago even when purchased items are visible', function () {
    $user = User::factory()->create();

    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Formatge pendent',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Iogurt antic',
        'purchased' => true,
        'purchased_at' => now()->subDays(8),
        'position' => 2,
    ]);

    $this->actingAs($user);

    $this->get(route('shopping-list.full', ['purchase' => 'all']))
        ->assertSuccessful()
        ->assertSee('Formatge pendent')
        ->assertDontSee('Iogurt antic');
});

test('full shopping list can toggle the pending-only filter on and off', function () {
    $user = User::factory()->create();

    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Pendent toggle',
        'purchased' => false,
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($user)->create([
        'name' => 'Comprat toggle',
        'purchased' => true,
        'position' => 2,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::full-shopping-list')
        ->assertSet('purchaseFilter', 'pending')
        ->assertSee('Pendent toggle')
        ->assertDontSee('Comprat toggle')
        ->call('togglePurchasedVisibility')
        ->assertSet('purchaseFilter', 'all')
        ->assertSee('Pendent toggle')
        ->assertSee('Comprat toggle')
        ->call('togglePurchasedVisibility')
        ->assertSet('purchaseFilter', 'pending')
        ->assertSee('Pendent toggle')
        ->assertDontSee('Comprat toggle');
});

test('full shopping list shows pending items before purchased ones when purchased items are visible', function () {
    $user = User::factory()->create();

    $shop = Shop::factory()->for($user)->create([
        'name' => 'Mercat',
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

    $this->get(route('shopping-list.full', ['purchase' => 'all']))
        ->assertSuccessful()
        ->assertSeeInOrder(['Pendent segon', 'Comprat primer']);
});

test('full shopping list can filter items by one or more shops', function () {
    $user = User::factory()->create();

    $fruitShop = Shop::factory()->for($user)->create([
        'name' => 'Fruita',
        'position' => 1,
    ]);

    $bakeryShop = Shop::factory()->for($user)->create([
        'name' => 'Forn',
        'position' => 2,
    ]);

    $cleaningShop = Shop::factory()->for($user)->create([
        'name' => 'Neteja',
        'position' => 3,
    ]);

    ShoppingListItem::factory()->for($fruitShop)->for($user)->create([
        'name' => 'Poma filtrada',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($bakeryShop)->for($user)->create([
        'name' => 'Pa filtrat',
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($cleaningShop)->for($user)->create([
        'name' => 'Sabó filtrat',
        'position' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::full-shopping-list')
        ->assertSee('Poma filtrada')
        ->assertSee('Pa filtrat')
        ->assertSee('Sabó filtrat')
        ->call('toggleShopFilter', $fruitShop->id)
        ->assertSet('selectedShopIds', [$fruitShop->id])
        ->assertSee('Poma filtrada')
        ->assertDontSee('Pa filtrat')
        ->assertDontSee('Sabó filtrat')
        ->call('toggleShopFilter', $bakeryShop->id)
        ->assertSet('selectedShopIds', [$fruitShop->id, $bakeryShop->id])
        ->assertSee('Poma filtrada')
        ->assertSee('Pa filtrat')
        ->assertDontSee('Sabó filtrat');
});

test('master users are redirected from the full shopping list page', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->get(route('shopping-list.full'))
        ->assertRedirect(route('master.index'));
});
