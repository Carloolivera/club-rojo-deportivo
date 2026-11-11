<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's language to the whole request.
 *
 * The language comes from the session (set by the /idioma/{locale} route).
 * Anything outside the allowed list falls back to the app default, so a
 * tampered session can never load an arbitrary translation file.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (in_array($locale, config('club.locales'), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
