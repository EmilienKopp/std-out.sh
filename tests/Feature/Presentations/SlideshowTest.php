<?php

declare(strict_types=1);

use App\Models\Presentation;
use Inertia\Testing\AssertableInertia as Assert;

test('the slideshow page renders for any visitor using the embed token', function () {
    $presentation = Presentation::factory()->withSlides(3)->create();

    $response = $this->get(
        route('presentations.slideshow', ['presentation' => $presentation->embed_token]),
    );

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Slideshow')
        ->where('presentation.name', $presentation->name)
        ->count('presentation.content.slides', 3),
    );
});

test('the slideshow page exposes no live secrets', function () {
    $presentation = Presentation::factory()->create();

    $this->get(route('presentations.slideshow', ['presentation' => $presentation->embed_token]))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('remote')
            ->missing('sessionRoutes')
            ->missing('presentation.yoyotranslate')
            ->missing('presentation.talk_settings'),
        );
});

test('a private deck has no public slideshow', function () {
    $presentation = Presentation::factory()->create(['is_private' => true]);

    $this->get(route('presentations.slideshow', ['presentation' => $presentation->embed_token]))
        ->assertNotFound();
});

test('the slideshow page carries its own Open Graph tags', function () {
    $presentation = Presentation::factory()->withSlides(3)->create();

    $response = $this->get(
        route('presentations.slideshow', ['presentation' => $presentation->embed_token]),
    );

    $response->assertOk();
    $response->assertSee('<meta property="og:title" content="'.e($presentation->name).'">', false);
    $response->assertSee('A 3-slide presentation on', false);
    $response->assertSee(
        '<meta property="og:image" content="'.route('presentations.card', ['presentation' => $presentation->embed_token]).'">',
        false,
    );
});

test('the slideshow card renders a PNG for any visitor', function () {
    $presentation = Presentation::factory()->withSlides(3)->create();

    $response = $this->get(
        route('presentations.card', ['presentation' => $presentation->embed_token]),
    );

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

test('a private deck has no slideshow card', function () {
    $presentation = Presentation::factory()->create(['is_private' => true]);

    $this->get(route('presentations.card', ['presentation' => $presentation->embed_token]))
        ->assertNotFound();
});
