<?php

test('welcome page showcases the branded landing page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('NoCompris')
        ->assertSee('La llista compartida que converteix el caos en rutina clara.')
        ->assertSee('Inicia sessió');
});
