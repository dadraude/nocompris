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
        ->assertSee('Usuaris i grups')
        ->assertSee('max-w-[90rem]', false)
        ->assertSee('xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]', false)
        ->assertSee('rounded-xl', false);
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

test('master user can assign and remove a user from a group', function () {
    $master = User::factory()->master()->create();
    $firstGroup = UserGroup::factory()->create();
    $secondGroup = UserGroup::factory()->create();
    $user = User::factory()->inGroup($firstGroup)->create();

    $this->actingAs($master);

    Livewire::test('pages::master-access')
        ->assertSet("userGroupAssignments.{$user->id}", (string) $firstGroup->id)
        ->call('updateUserGroup', $user->id, (string) $secondGroup->id)
        ->assertHasNoErrors()
        ->assertSet("userGroupAssignments.{$user->id}", (string) $secondGroup->id);

    expect($user->refresh()->user_group_id)->toBe($secondGroup->id);

    Livewire::test('pages::master-access')
        ->call('updateUserGroup', $user->id, '')
        ->assertHasNoErrors()
        ->assertSet("userGroupAssignments.{$user->id}", '');

    expect($user->refresh()->user_group_id)->toBeNull();
});
