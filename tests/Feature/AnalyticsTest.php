<?php

const TRACKER = 'analytics.aloflux.com/tracker.js';

test('the live site loads the analytics tracker', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee(TRACKER);
});

test('the analytics tracker never loads on a page whose address holds a reset token', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get(route('password.reset', ['token' => 'secret-reset-token']))
        ->assertOk()
        ->assertDontSee(TRACKER);
});

test('the analytics tracker stays off outside the live site', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee(TRACKER);
});
