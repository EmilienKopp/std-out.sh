<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\Queries\PresentationQuery;
use App\Models\Presentation;
use Inertia\Inertia;
use Inertia\Response;

class SlideshowController extends Controller
{
    public function __construct(private readonly PresentationQuery $presentations) {}

    public function __invoke(Presentation $presentation): Response
    {
        // A private deck's slideshow URL should not even confirm the deck exists.
        abort_if($presentation->is_private, 404);

        $slideCount = count($presentation->content['slides'] ?? []);

        return Inertia::render('presentations/Slideshow', [
            'presentation' => $this->presentations->findForSlideshow($presentation->id),
            'sourcePdfUrl' => $presentation->sourcePdfUrl(),
        ])->withViewData([
            'metaTitle' => $presentation->name,
            'metaDescription' => "A {$slideCount}-slide presentation on ".config('app.name', 'Tecturn').'.',
            'metaImage' => route('presentations.card', ['presentation' => $presentation->embed_token]),
        ]);
    }
}
