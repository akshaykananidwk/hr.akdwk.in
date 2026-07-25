<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow the installer, health check and PWA asset routes through.
        if ($request->is('install', 'install/*', 'up', 'sw.js', 'manifest.webmanifest')) {
            return $next($request);
        }

        if (! app_installed()) {
            return redirect('/install');
        }

        return $next($request);
    }
}
