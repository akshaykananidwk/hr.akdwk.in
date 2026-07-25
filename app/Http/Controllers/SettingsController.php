<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage settings'), 403);

        $settings = Setting::whereIn('group', ['company', 'attendance'])->get()->keyBy('key');
        $policies = Setting::where('group', 'policy')->get();
        $whatsapp = Setting::where('group', 'whatsapp')->get()->keyBy('key');

        return view('settings.index', compact('settings', 'policies', 'whatsapp'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403);

        $data = $request->validate([
            'company_name' => ['nullable', 'string'],
            'company_tagline' => ['nullable', 'string'],
            'company_email' => ['nullable', 'email'],
            'company_phone' => ['nullable', 'string'],
            'company_address' => ['nullable', 'string'],
            'currency_symbol' => ['nullable', 'string', 'max:5'],
            'work_start_time' => ['nullable', 'string'],
            'late_after_time' => ['nullable', 'string'],
            'full_day_hours' => ['nullable', 'numeric'],
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $group = str_starts_with($key, 'company') || $key === 'currency_symbol' ? 'company' : 'attendance';
                Setting::put($key, $value, $group);
            }
        }
        ActivityLogger::log('settings.update', null, 'Updated company settings');

        return back()->with('success', 'Settings saved.');
    }

    public function updatePolicy(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403);
        $data = $request->validate([
            'key' => ['required', 'string'],
            'value' => ['required', 'string'],
        ]);
        Setting::put($data['key'], $data['value'], 'policy');

        return back()->with('success', 'Policy updated.');
    }

    public function updateWhatsapp(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403);
        $data = $request->validate([
            'whatsapp_enabled' => ['nullable'],
            'whatsapp_api_key' => ['nullable', 'string'],
            'whatsapp_session_id' => ['nullable', 'string'],
            'whatsapp_group_id' => ['nullable', 'string'],
            'whatsapp_admin_mobile' => ['nullable', 'string'],
            'whatsapp_msg_in' => ['nullable', 'string'],
            'whatsapp_msg_out' => ['nullable', 'string'],
            'whatsapp_msg_lunch_out' => ['nullable', 'string'],
            'whatsapp_msg_lunch_in' => ['nullable', 'string'],
            'whatsapp_msg_leave' => ['nullable', 'string'],
        ]);

        Setting::put('whatsapp_enabled', $request->boolean('whatsapp_enabled') ? '1' : '0', 'whatsapp');
        foreach ($data as $key => $value) {
            if ($key === 'whatsapp_enabled') {
                continue;
            }
            Setting::put($key, $value ?? '', 'whatsapp');
        }
        ActivityLogger::log('settings.whatsapp', null, 'Updated WhatsApp settings');

        return back()->with('success', 'WhatsApp settings saved.');
    }

    public function testWhatsapp(Request $request, WhatsAppService $wa)
    {
        abort_unless(auth()->user()->can('manage settings'), 403);

        if (! $wa->enabled()) {
            return back()->with('error', 'Enable WhatsApp and save API key + session id first.');
        }

        $response = $wa->sendToGroup('🧪 *AK Workforce Pro*\nTest message — your WhatsApp group configuration works. ✅')
            ?? $wa->sendTo((string) Setting::get('whatsapp_admin_mobile'), '🧪 AK Workforce Pro test message ✅');

        return back()->with($response ? 'success' : 'error',
            $response ? 'Test message sent. Gateway response: '.Str::limit($response, 120) : 'Could not send — check group id / admin mobile.');
    }
}
