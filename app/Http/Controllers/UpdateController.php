<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\UpdateLog;
use App\Services\UpdateService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(private UpdateService $updater) {}

    public function index()
    {
        abort_unless(auth()->user()->can('manage updates'), 403);

        return view('updates.index', [
            'config' => $this->updater->config(),
            'configured' => $this->updater->isConfigured(),
            'hasToken' => (bool) Setting::get('github_token'),
            'logs' => UpdateLog::with('trigger')->latest()->limit(10)->get(),
        ]);
    }

    public function saveConfig(Request $request)
    {
        abort_unless(auth()->user()->can('manage updates'), 403);
        $data = $request->validate([
            'github_repo' => ['required', 'string', 'regex:/^[\w.-]+\/[\w.-]+$/'],
            'github_branch' => ['nullable', 'string'],
            'github_token' => ['nullable', 'string'],
        ]);

        $this->updater->saveConfig($data['github_repo'], $data['github_branch'] ?? 'main', $data['github_token'] ?? null);

        return back()->with('success', 'Update settings saved.');
    }

    /**
     * AJAX: check GitHub for a newer commit.
     */
    public function check()
    {
        abort_unless(auth()->user()->can('manage updates'), 403);
        try {
            return response()->json(['ok' => true, 'data' => $this->updater->checkForUpdate()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Apply the update (one click). Returns the resulting log.
     */
    public function update(Request $request)
    {
        abort_unless(auth()->user()->can('manage updates'), 403);
        $log = $this->updater->performUpdate(auth()->id());

        $success = $log->status === 'success';

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => $success,
                'status' => $log->status,
                'log' => $log->log,
                'error' => $log->error,
            ], $success ? 200 : 422);
        }

        return redirect()->route('updates.index')->with(
            $success ? 'success' : 'error',
            $success ? 'Update applied successfully.' : 'Update failed: '.$log->error
        );
    }
}
