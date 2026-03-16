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
        ->assertSee('Llistat complet')
        ->assertDontSee('Una llista simple per organitzar la compra compartida.')
        ->assertSee('data-test="mobile-refresh-button"', false)
        ->assertSee('window.location.replace(window.location.href)', false)
        ->assertSee('fixed inset-x-0 top-0 z-40', false)
        ->assertSee('data-flux-sidebar-on-mobile:top-14!', false)
        ->assertSee('data-flux-sidebar-on-mobile:bottom-0!', false)
        ->assertSee('data-flux-sidebar-on-mobile:min-h-0!', false)
        ->assertSee('lg:sticky lg:top-0 lg:max-h-dvh lg:overflow-y-auto lg:overscroll-contain', false)
        ->assertSee('hidden lg:block', false)
        ->assertSee('pt-14 lg:pt-0', false)
        ->assertSee('Refresca la pàgina');
});

test('master users are redirected from the dashboard to the master panel', function () {
    $master = User::factory()->master()->create();

    $response = $this->actingAs($master)->get(route('dashboard'));

    $response->assertRedirect(route('master.index'));
});
