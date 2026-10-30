<?php

namespace App\Http\Controllers\Profiles;

use App\Http\Controllers\Controller;
use App\Infrastructure\Queries\ActivityQuery;
use App\Models\User;
use App\Support\ActivityCalendarSvg;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Public, unauthenticated endpoint — serves a user's activity calendar as an
 * SVG so it can be embedded in READMEs and blog posts. Setting a handle is
 * what opts a user into this public surface; without one there is no URL.
 */
class ShowActivityGraphController extends Controller
{
    public function __construct(
        private readonly ActivityQuery $activity,
    ) {}

    public function __invoke(string $handle): Response
    {
        // v2 = std-out green palette; keyed so stale amber renders expire away.
        return Cache::remember("activity-graph-v2-$handle", 3600, fn (): Response => $this->render($handle));
    }

    private function render(string $handle): Response
    {
        $user = User::query()->where('handle', mb_strtolower($handle))->firstOrFail();

        $end = CarbonImmutable::today();
        $start = $end->subWeeks(52)->startOfWeek(CarbonImmutable::SUNDAY);

        $svg = ActivityCalendarSvg::render(
            $user->handle,
            $this->activity->calendarForUser($user->id, $start, $end),
            $start,
            $end,
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}
