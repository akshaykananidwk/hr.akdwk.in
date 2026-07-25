<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function toggleTheme(Request $request)
    {
        $current = $request->cookie('theme', 'light');
        $next = $current === 'dark' ? 'light' : 'dark';

        return back()->withCookie(cookie('theme', $next, 60 * 24 * 365));
    }

    public function switchLocale(string $locale, Request $request)
    {
        if (in_array($locale, ['en', 'gu'], true)) {
            session(['locale' => $locale]);
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
        }

        return back();
    }

    public function manifest()
    {
        return response()->json([
            'name' => 'AK Workforce Pro',
            'short_name' => 'AK Workforce',
            'start_url' => '/dashboard',
            'display' => 'standalone',
            'background_color' => '#4f46e5',
            'theme_color' => '#4f46e5',
            'icons' => [[
                'src' => 'https://ui-avatars.com/api/?background=4f46e5&color=fff&name=AK&size=192',
                'sizes' => '192x192', 'type' => 'image/png',
            ], [
                'src' => 'https://ui-avatars.com/api/?background=4f46e5&color=fff&name=AK&size=512',
                'sizes' => '512x512', 'type' => 'image/png',
            ]],
        ])->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker()
    {
        $js = <<<'JS'
        const CACHE = 'akwp-v1';
        self.addEventListener('install', e => self.skipWaiting());
        self.addEventListener('activate', e => self.clients.claim());
        self.addEventListener('fetch', event => {
            if (event.request.method !== 'GET') return;
            event.respondWith(
                fetch(event.request).catch(() => caches.match(event.request))
            );
        });
        JS;

        return response($js)->header('Content-Type', 'application/javascript');
    }
}
