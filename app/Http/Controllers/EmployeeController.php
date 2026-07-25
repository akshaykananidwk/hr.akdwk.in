<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function __construct()
    {
        // Guarded by route middleware; extra safety here.
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view employees'), 403);
        $query = User::with(['department', 'designation', 'branch', 'roles'])->latest();
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->q}%")
                ->orWhere('email', 'like', "%{$request->q}%")
                ->orWhere('employee_code', 'like', "%{$request->q}%"));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return view('employees.index', [
            'employees' => $query->paginate(15)->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('manage employees'), 403);

        return view('employees.form', $this->formData(new User));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage employees'), 403);
        $data = $this->validateEmployee($request);

        $user = User::create([
            'name' => $data['name'],
            'employee_code' => $data['employee_code'] ?: $this->nextCode(),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'branch_id' => $data['branch_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'designation_id' => $data['designation_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'date_of_joining' => $data['date_of_joining'] ?? null,
            'status' => $data['status'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$data['role']]);
        EmployeeProfile::create(['user_id' => $user->id] + $this->profileData($request));
        ActivityLogger::log('employee.create', $user, "Created employee: {$user->name}");

        return redirect()->route('employees.show', $user)->with('success', 'Employee created.');
    }

    public function show(User $employee)
    {
        abort_unless(auth()->user()->can('view employees'), 403);
        $employee->load(['department', 'designation', 'branch', 'manager', 'roles', 'profile', 'documents', 'policyAcceptances']);

        return view('employees.show', compact('employee'));
    }

    public function edit(User $employee)
    {
        abort_unless(auth()->user()->can('manage employees'), 403);
        $employee->load('profile');

        return view('employees.form', $this->formData($employee));
    }

    public function update(Request $request, User $employee)
    {
        abort_unless(auth()->user()->can('manage employees'), 403);
        $data = $this->validateEmployee($request, $employee);

        $employee->update([
            'name' => $data['name'],
            'employee_code' => $data['employee_code'] ?: $employee->employee_code,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'designation_id' => $data['designation_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'date_of_joining' => $data['date_of_joining'] ?? null,
            'status' => $data['status'],
        ]);
        if (! empty($data['password'])) {
            $employee->update(['password' => Hash::make($data['password'])]);
        }
        $employee->syncRoles([$data['role']]);
        EmployeeProfile::updateOrCreate(['user_id' => $employee->id], $this->profileData($request));
        ActivityLogger::log('employee.update', $employee, "Updated employee: {$employee->name}");

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function toggleActive(User $employee)
    {
        abort_unless(auth()->user()->can('manage employees'), 403);
        $employee->update(['is_active' => ! $employee->is_active]);

        return back()->with('success', 'Employee '.($employee->is_active ? 'activated' : 'deactivated').'.');
    }

    private function validateEmployee(Request $request, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'employee_code' => ['nullable', 'string', Rule::unique('users', 'employee_code')->ignore($employee?->id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$employee ? 'nullable' : 'required', 'nullable', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'date_of_joining' => ['nullable', 'date'],
            'status' => ['required', 'in:active,probation,inactive,terminated'],
            // profile
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'aadhar_number' => ['nullable', 'string'],
            'pan_number' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'bank_ifsc' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string'],
            'emergency_contact_phone' => ['nullable', 'string'],
        ]);
    }

    private function profileData(Request $request): array
    {
        return $request->only([
            'basic_salary', 'blood_group', 'aadhar_number', 'pan_number',
            'bank_name', 'bank_account_number', 'bank_ifsc',
            'emergency_contact_name', 'emergency_contact_phone',
        ]);
    }

    private function formData(User $employee): array
    {
        return [
            'employee' => $employee,
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'managers' => User::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ];
    }

    private function nextCode(): string
    {
        return 'AK'.str_pad((string) (User::max('id') + 1), 4, '0', STR_PAD_LEFT);
    }
}
