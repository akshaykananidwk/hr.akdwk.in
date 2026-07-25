<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Accept either an email address or a phone number (legacy staff log in
        // with their phone number, exactly like the old attendance system).
        $identifier = trim($request->input('login'));
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        if ($field === 'phone') {
            $identifier = preg_replace('/[^0-9]/', '', $identifier);
        }

        $credentials = [$field => $identifier, 'password' => $request->input('password')];
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            if ($user = User::where($field, $identifier)->first()) {
                $this->recordLogin($user->id, $request, false);
            }

            throw ValidationException::withMessages([
                'login' => __('These credentials do not match our records.'),
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => __('Your account is inactive. Please contact your administrator.'),
            ]);
        }

        $request->session()->regenerate();
        $this->recordLogin($user->id, $request, true);
        ActivityLogger::log('login', $user, 'User logged in');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        ActivityLogger::log('logout', Auth::user(), 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function recordLogin(int $userId, Request $request, bool $successful): void
    {
        LoginHistory::create([
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'platform' => $this->guessPlatform($request->userAgent() ?? ''),
            'successful' => $successful,
            'logged_in_at' => now(),
        ]);
    }

    private function guessPlatform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown',
        };
    }
}
