<?php

use App\Models\User;
use App\Notifications\Auth\LoginCodeNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());
});

test('two factor challenge redirects to login when not authenticated', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    Notification::fake();

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

    $this->post(route('login.verify.store'), [
        'code' => $code,
    ])->assertRedirect(route('two-factor.login'));

    $this->get(route('two-factor.login'))
        ->assertOk();
});
