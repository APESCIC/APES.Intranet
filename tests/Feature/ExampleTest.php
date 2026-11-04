<?php

it('shows the welcome page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('data-lucide="house"', false);
});
