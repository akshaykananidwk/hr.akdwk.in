<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('view activity log'), 403);

        return view('activity.index', [
            'logs' => ActivityLog::with('user')->latest()->paginate(30),
        ]);
    }
}
