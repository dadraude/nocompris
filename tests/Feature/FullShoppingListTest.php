<?php

use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Models\UserGroup;
use App\ShoppingListItemVisibility;
use Livewire\Livewire;

test('authenticated user can visit the full shopping list page from the sidebar navigation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(route('shopping-list.full', absolute: false))->toBe('/full-shopping-list');

    $this->get(route('shopping-list.full'))
        ->assertSuccessful()
        ->assertSee('Llistat complet')
        ->assertSee('Organitza per')
        ->assertSee('data-test="full-list-sort-select"', false)
        ->assertSee('Llista de la compra');
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
        'position' => 1,
    ]);

    ShoppingListItem::factory()->for($shop)->for($groupMember)->asPrivate()->create([
        'name' => 'Secret privat',
        'quantity' => 2,
        'position' => 2,
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
        ->assertDontSee('Secret privat');
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
        ->call('togglePurchased', $item->id);

    expect($item->refresh()->purchased)->toBeTrue();
});

test('master users are redirected from the full shopping list page', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->get(route('shopping-list.full'))
        ->assertRedirect(route('master.index'));
});
