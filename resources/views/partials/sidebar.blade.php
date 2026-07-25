@php
    $nav = fn($route) => request()->routeIs($route) ? 'active' : '';
@endphp
<nav class="ak-sidebar">
    <div class="brand">
        <div class="logo"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="fw-bold" style="font-size:1rem">AK Workforce</div>
            <small style="color:#8b93b0;font-size:.7rem">Pro Enterprise</small>
        </div>
    </div>

    <div class="nav-section">Main</div>
    <a class="nav-link {{ $nav('dashboard') }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a class="nav-link {{ $nav('attendance.*') }}" href="{{ route('attendance.index') }}"><i class="bi bi-fingerprint"></i> Attendance</a>
    <a class="nav-link {{ $nav('reports.*') }}" href="{{ route('reports.index') }}"><i class="bi bi-clipboard-data"></i> Daily Reports</a>
    <a class="nav-link {{ $nav('tasks.*') }}" href="{{ route('tasks.index') }}"><i class="bi bi-check2-square"></i> Tasks</a>
    <a class="nav-link {{ $nav('leaves.*') }}" href="{{ route('leaves.index') }}"><i class="bi bi-calendar-x"></i> Leave</a>

    @canany(['manage leads','view leads'])
    <div class="nav-section">Sales &amp; CRM</div>
    <a class="nav-link {{ $nav('leads.*') }}" href="{{ route('leads.index') }}"><i class="bi bi-funnel"></i> Leads</a>
    @endcanany
    @canany(['view products','manage products'])
    <a class="nav-link {{ $nav('products.*') }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam"></i> Products</a>
    @endcanany
    @canany(['view sales','manage sales'])
    <a class="nav-link {{ $nav('sales.*') }}" href="{{ route('sales.index') }}"><i class="bi bi-receipt"></i> Sales</a>
    @endcanany
    @canany(['view commissions','manage commissions'])
    <a class="nav-link {{ $nav('commissions.*') }}" href="{{ route('commissions.index') }}"><i class="bi bi-wallet2"></i> Commission</a>
    @endcanany

    @canany(['manage employees','view employees'])
    <div class="nav-section">HR</div>
    <a class="nav-link {{ $nav('employees.*') }}" href="{{ route('employees.index') }}"><i class="bi bi-person-vcard"></i> Employees</a>
    @endcanany
    @can('approve leave')
    <a class="nav-link {{ $nav('leaves.approvals') }}" href="{{ route('leaves.approvals') }}"><i class="bi bi-calendar-check"></i> Leave Approvals</a>
    @endcan

    @canany(['manage settings','manage updates'])
    <div class="nav-section">Admin</div>
    @can('manage settings')
    <a class="nav-link {{ $nav('settings.index') }}" href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Settings</a>
    @endcan
    @can('manage updates')
    <a class="nav-link {{ $nav('updates.*') }}" href="{{ route('updates.index') }}"><i class="bi bi-cloud-arrow-down"></i> System Update</a>
    @endcan
    @can('view activity log')
    <a class="nav-link {{ $nav('activity.*') }}" href="{{ route('activity.index') }}"><i class="bi bi-clock-history"></i> Activity Log</a>
    @endcan
    @endcanany

    <div class="nav-section">Account</div>
    <a class="nav-link {{ $nav('profile.*') }}" href="{{ route('profile.show') }}"><i class="bi bi-person-circle"></i> My Profile</a>
    <form action="{{ route('logout') }}" method="POST" class="px-2 mt-2 mb-4">@csrf
        <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i> Logout</button>
    </form>
</nav>
