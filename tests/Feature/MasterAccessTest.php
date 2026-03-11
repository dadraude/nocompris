<?php

use App\Models\User;
use App\Models\UserGroup;
use Livewire\Livewire;

test('non master users cannot access the master panel', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('master.index'));

    $response->assertForbidden();
});

test('master users can access the master panel', function () {
    $master = User::factory()->master()->create();

    $response = $this->actingAs($master)->get(route('master.index'));

    $response->assertSuccessful()
        ->assertSee('Usuaris i grups');
});

test('master user can create groups and users from the panel', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master);

    Livewire::test('pages::master-access')
        ->set('groupName', 'Família Costa')
        ->call('createGroup')
        ->assertHasNoErrors();

    $group = UserGroup::query()->where('name', 'Família Costa')->first();

    expect($group)->not->toBeNull();

    Livewire::test('pages::master-access')
        ->set('userName', 'Usuari Compartit')
        ->set('userEmail', 'compartit@example.com')
        ->set('userPassword', 'password')
        ->set('userPasswordConfirmation', 'password')
        ->set('userGroupId', (string) $group?->id)
        ->set('userIsMaster', false)
        ->call('createUser')
        ->assertHasNoErrors();

    $createdUser = User::query()->where('email', 'compartit@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser?->user_group_id)->toBe($group?->id);
    expect($createdUser?->is_master)->toBeFalse();
});
