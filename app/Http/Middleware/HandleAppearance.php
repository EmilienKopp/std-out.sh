<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * The app theme is locked to the std-out pair; only the appearance
     * cookie (light/dark/system) picks between them. Legacy 'theme' cookies
     * from the pinned-theme era are ignored and expire client-side.
     * 'system' resolves client-side and defaults to light here, matching
     * previous behavior.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance');
        $appearance = is_string($appearance) ? $appearance : 'system';

        $isDark = $appearance === 'dark';

        View::share('appearance', $appearance);
        View::share('theme', $isDark ? 'stdout-dark' : 'stdout');
        View::share('themeIsDark', $isDark);

        return $next($request);
    }
}
