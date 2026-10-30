<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Models\Presentation;
use App\Support\PresentationCardPng;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Serves the Open Graph preview image for a public slideshow URL. Anonymous
 * like the slideshow itself; a private deck's card URL must not even confirm
 * the deck exists.
 */
class ShowSlideshowCardController extends Controller
{
    public function __invoke(Presentation $presentation): Response
    {
        abort_if($presentation->is_private, 404);

        $cacheKey = "presentation-card-{$presentation->id}-".($presentation->updated_at?->getTimestamp() ?? 0);

        $png = Cache::remember($cacheKey, 3600, fn (): string => PresentationCardPng::render(
            $presentation->name,
            $presentation->content['slides'][0]['background'] ?? null,
            config('app.name', 'std-out'),
        ));

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}
