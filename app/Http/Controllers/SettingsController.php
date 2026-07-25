<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage settings'), 403);

        $settings = Setting::whereIn('group', ['company', 'attendance'])->get()->keyBy('key');
        $policies = Setting::where('group', 'policy')->get();

        return view('settings.index', compact('settings', 'policies'));
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
}
