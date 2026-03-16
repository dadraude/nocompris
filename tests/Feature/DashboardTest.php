<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertSee('NoCompris')
        ->assertSee('Organitza la compra')
        ->assertSee('data-test="mobile-refresh-button"', false)
        ->assertSee('window.location.reload()', false)
        ->assertSee('Refresca la pàgina');
});

test('master users are redirected from the dashboard to the master panel', function () {
    $master = User::factory()->master()->create();

    $response = $this->actingAs($master)->get(route('dashboard'));

    $response->assertRedirect(route('master.index'));
});
