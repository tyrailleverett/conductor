<?php

declare(strict_types=1);

use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('serves the blade shell view at the base conductor path', function (): void {
    $this->get('/conductor')
        ->assertSuccessful()
        ->assertSee('<div id="app"></div>', false);
});

it('serves the blade shell for deep-linked spa paths', function (): void {
    $this->get('/conductor/jobs/some-uuid')->assertSuccessful();
});

it('serves the api prefix via api route group', function (): void {
    $this->getJson('/conductor/api/jobs')->assertSuccessful();
});

it('reserves the webhook prefix for webhook routes', function (): void {
    $this->post('/conductor/webhook/stripe')->assertNotFound();
});
