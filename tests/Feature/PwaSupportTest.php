<?php

test('login page exposes pwa metadata', function () {
    $response = $this->get(route('login'));

    $response
        ->assertOk()
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('theme-color', false)
        ->assertSee('mobile-web-app-capable', false)
        ->assertSee('apple-mobile-web-app-capable', false)
        ->assertSee('apple-mobile-web-app-title', false);
});

test('pwa assets are published with the expected configuration', function () {
    $manifest = json_decode(
        file_get_contents(public_path('manifest.webmanifest')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)
        ->toMatchArray([
            'name' => 'NoCompris',
            'short_name' => 'NoCompris',
            'start_url' => '/login',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f7f3ec',
            'theme_color' => '#f7f3ec',
            'lang' => 'ca',
        ])
        ->and($manifest['icons'])->toBeArray();

    $iconSources = collect($manifest['icons'])->pluck('src')->all();

    expect($iconSources)
        ->toContain('/pwa-192x192.png')
        ->toContain('/pwa-512x512.png');

    expect(public_path('manifest.webmanifest'))->toBeFile()
        ->and(public_path('sw.js'))->toBeFile()
        ->and(public_path('offline.html'))->toBeFile()
        ->and(public_path('pwa-192x192.png'))->toBeFile()
        ->and(public_path('pwa-512x512.png'))->toBeFile();

    expect(file_get_contents(resource_path('js/app.js')))
        ->toContain("navigator.serviceWorker.register('/sw.js'")
        ->toContain("document.addEventListener('livewire:navigate', startAppLoading);")
        ->toContain("document.addEventListener('livewire:navigated', () => {")
        ->toContain('const APP_LOADING_MIN_DURATION = 420;');

    expect(file_get_contents(public_path('sw.js')))
        ->toContain('/offline.html');
});
