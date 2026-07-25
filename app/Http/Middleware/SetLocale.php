<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale')
            ?? (auth()->check() ? auth()->user()->locale : null)
            ?? config('app.locale');

        if (in_array($locale, ['en', 'gu'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
