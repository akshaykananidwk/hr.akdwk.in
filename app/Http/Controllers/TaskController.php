<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canAssign = $user->hasAnyRole(['Super Admin', 'HR Manager', 'Sales Manager', 'Team Leader']);

        $query = Task::with(['assignee', 'assigner'])->latest();
        if (! $canAssign) {
            $query->where('assigned_to', $user->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('tasks.index', [
            'tasks' => $query->paginate(15)->withQueryString(),
            'canAssign' => $canAssign,
            'users' => $canAssign ? User::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'HR Manager', 'Sales Manager', 'Team Leader']), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:users,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
        ]);
        $data['assigned_by'] = auth()->id();
        $task = Task::create($data);
        ActivityLogger::log('task.create', $task, "Assigned task: {$task->title}");

        return back()->with('success', 'Task assigned.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        abort_unless($task->assigned_to === auth()->id() || $task->assigned_by === auth()->id() || auth()->user()->hasRole('Super Admin'), 403);
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'completion' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $data['completion'] = $data['status'] === 'completed' ? 100 : ($data['completion'] ?? $task->completion);
        $task->update($data);

        return back()->with('success', 'Task updated.');
    }
}
