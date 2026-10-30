<?php

it('defaults to the stdout light theme', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-theme="stdout"', false);
});

it('serves stdout-dark with the dark class when appearance is dark', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="stdout-dark"', false)
        ->assertSee('class="dark"', false);
});

it('ignores a pinned daisyui theme cookie from the pinned-theme era', function () {
    $this->withUnencryptedCookie('theme', 'dracula')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="stdout"', false)
        ->assertDontSee('class="dark"', false);
});

it('ignores a legacy onair theme cookie and follows appearance', function () {
    $this->withUnencryptedCookie('theme', 'onair-dark')
        ->withUnencryptedCookie('appearance', 'dark')
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="stdout-dark"', false)
        ->assertSee('class="dark"', false);
});
