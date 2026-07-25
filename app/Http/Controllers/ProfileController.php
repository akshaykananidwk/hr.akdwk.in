<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\PolicyAcceptance;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load(['profile', 'department', 'designation', 'branch', 'documents', 'policyAcceptances', 'roles']);

        // Policies still awaiting acceptance.
        $accepted = $user->policyAcceptances->pluck('policy_type')->all();
        $policies = collect(Setting::where('group', 'policy')->pluck('key'))
            ->mapWithKeys(fn ($k) => [str_replace('policy_', '', $k) => str_replace('policy_', '', $k)]);

        return view('profile.show', compact('user', 'accepted', 'policies'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'current_address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string'],
            'emergency_contact_phone' => ['nullable', 'string'],
        ]);

        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email']]);
        $user->profile()->updateOrCreate(['user_id' => $user->id], $request->only([
            'current_address', 'emergency_contact_name', 'emergency_contact_phone',
        ]));
        ActivityLogger::log('profile.update', $user, 'Updated profile');

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        auth()->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed.');
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $user = auth()->user();
        $version = Document::where('user_id', $user->id)->where('type', $request->type)->max('version') + 1;
        $path = $request->file('file')->store("documents/{$user->id}", 'public');

        Document::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'title' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $request->file('file')->getMimeType(),
            'size' => $request->file('file')->getSize(),
            'version' => $version,
            'expiry_date' => $request->expiry_date,
            'status' => 'pending',
        ]);
        ActivityLogger::log('document.upload', $user, "Uploaded {$request->type}");

        return back()->with('success', 'Document uploaded (pending verification).');
    }

    public function acceptPolicy(Request $request)
    {
        $data = $request->validate([
            'policy_type' => ['required', 'string'],
            'signature' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        PolicyAcceptance::updateOrCreate(
            ['user_id' => auth()->id(), 'policy_type' => $data['policy_type']],
            [
                'policy_version' => '1.0',
                'signature' => $data['signature'] ?? null,
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accepted_at' => now(),
            ]
        );
        ActivityLogger::log('policy.accept', auth()->user(), "Accepted policy: {$data['policy_type']}");

        return back()->with('success', 'Policy accepted and logged.');
    }
}
