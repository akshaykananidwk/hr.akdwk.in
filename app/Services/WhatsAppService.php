<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp notifications via the AK Bulk WhatsApp gateway (bulk.akdwk.in/api.php),
 * replicating the legacy attendance system's integration.
 *
 * Config is stored in settings (group 'whatsapp'):
 *   whatsapp_api_key, whatsapp_session_id, whatsapp_group_id, whatsapp_admin_mobile,
 *   whatsapp_enabled, and message templates whatsapp_msg_in / _out / _lunch_out /
 *   _lunch_in / _leave / _client_task.
 */
class WhatsAppService
{
    private string $endpoint = 'https://bulk.akdwk.in/api.php';

    public function enabled(): bool
    {
        return (bool) Setting::get('whatsapp_enabled', false)
            && Setting::get('whatsapp_api_key')
            && Setting::get('whatsapp_session_id');
    }

    /**
     * Normalise an Indian number: strip non-digits, prefix 91 for 10-digit numbers.
     */
    public function normalize(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number) ?? '';
        if (strlen($number) === 10) {
            $number = '91'.$number;
        }

        return $number;
    }

    /**
     * Send a direct message to a phone number. Returns the raw gateway response
     * or null when disabled / on failure (never throws).
     */
    public function sendTo(string $number, string $message): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $number = $this->normalize($number);
        if ($number === '') {
            return null;
        }

        try {
            $response = Http::timeout(5)->get($this->endpoint, [
                'number' => $number,
                'message' => $message,
                'session_id' => Setting::get('whatsapp_session_id'),
                'api_key' => Setting::get('whatsapp_api_key'),
            ]);

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Send a message to the configured WhatsApp group.
     */
    public function sendToGroup(string $message): ?string
    {
        $group = Setting::get('whatsapp_group_id');
        if (! $group) {
            return null;
        }

        // The group id (e.g. 123456789@g.us) is passed as the "number".
        return $this->sendRaw($group, $message);
    }

    /**
     * Send using an already-final recipient (number or group id) without 91-prefixing.
     */
    public function sendRaw(string $recipient, string $message): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get($this->endpoint, [
                'number' => str_contains($recipient, '@g.us') ? $recipient : $this->normalize($recipient),
                'message' => $message,
                'session_id' => Setting::get('whatsapp_session_id'),
                'api_key' => Setting::get('whatsapp_api_key'),
            ]);

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp group send failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Render a template replacing {name}, {time}, {date} placeholders.
     */
    public function render(string $template, array $vars = []): string
    {
        $replacements = [
            '{name}' => $vars['name'] ?? '',
            '{time}' => $vars['time'] ?? '',
            '{date}' => $vars['date'] ?? now()->format('d-m-Y'),
        ];

        return strtr($template, $replacements);
    }

    /**
     * Convenience: notify an attendance event using the matching template and,
     * optionally, mirror it to the group.
     */
    public function attendanceEvent(string $event, User $user, ?string $time = null): void
    {
        $templates = [
            'in' => Setting::get('whatsapp_msg_in', 'Hello {name}, your IN time ({time}) is recorded.'),
            'out' => Setting::get('whatsapp_msg_out', 'Hello {name}, your OUT time ({time}) is recorded.'),
            'lunch_out' => Setting::get('whatsapp_msg_lunch_out', 'Hello {name}, your LUNCH OUT time is {time}.'),
            'lunch_in' => Setting::get('whatsapp_msg_lunch_in', 'Hello {name}, your LUNCH IN time is {time}.'),
            'leave' => Setting::get('whatsapp_msg_leave', 'Hello {name}, your LEAVE for today is approved.'),
        ];

        $template = $templates[$event] ?? null;
        if (! $template || ! $user->whatsapp_opt_in) {
            return;
        }

        $message = $this->render($template, [
            'name' => $user->name,
            'time' => $time ?? now()->format('h:i A'),
        ]);

        if ($user->phone) {
            $this->sendTo($user->phone, $message);
        }
    }
}
