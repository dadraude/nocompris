<?php

use App\Models\User;
use App\Notifications\Auth\LoginCodeNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login page shows login form and expected content', function () {
    $response = $this->get(route('login'));

    $response
        ->assertOk()
        ->assertSee('Email sign in')
        ->assertSee('Email address')
        ->assertSee('Continue with email')
        ->assertSee('Keep me signed in on this device')
        ->assertDontSee('Password')
        ->assertDontSee('Forgot your password?')
        ->assertDontSee('Don\'t have an account?')
        ->assertDontSee('Llista domèstica compartida');
});

test('users can request a login code', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post(route('login.email.send'), [
        'email' => $user->email,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login.verify', absolute: false));

    Notification::assertSentTo($user, LoginCodeNotification::class);

    $this->assertGuest();
});

test('users can authenticate using a valid email code', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('login.email.send'), [
        'email' => $user->email,
    ]);

    $code = null;

    Notification::assertSentTo($user, LoginCodeNotification::class, function (LoginCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $response = $this->post(route('login.verify.store'), [
        'code' => $code,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('master users are redirected to the master panel after login', function () {
    Notification::fake();

    $user = User::factory()->master()->create();

    $this->post(route('login.email.send'), [
        'email' => $user->email,
    ]);

    $code = null;

    Notification::assertSentTo($user, LoginCodeNotification::class, function (LoginCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $response = $this->post(route('login.verify.store'), [
        'code' => $code,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('master.index', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with an invalid login code', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('login.email.send'), [
        'email' => $user->email,
    ]);

    $response = $this->post(route('login.verify.store'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrorsIn('code');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    Notification::fake();

    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.email.send'), [
        'email' => $user->email,
    ]);

    $code = null;

    Notification::assertSentTo($user, LoginCodeNotification::class, function (LoginCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $response = $this->post(route('login.verify.store'), [
        'code' => $code,
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
