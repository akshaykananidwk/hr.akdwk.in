<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ request()->cookie('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · AK Workforce Pro</title>
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#4f46e5">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --ak-primary:#4f46e5; --ak-primary-2:#7c3aed; --ak-sidebar:#1e1b3a; }
        body { font-family:'Segoe UI',system-ui,sans-serif; background:#f5f6fa; }
        [data-bs-theme="dark"] body { background:#12121c; }
        .ak-sidebar {
            width:260px; position:fixed; top:0; left:0; bottom:0; z-index:1040;
            background:linear-gradient(180deg,#1e1b3a,#2a1f52); color:#cbd3e0; overflow-y:auto;
            transition:transform .25s ease;
        }
        .ak-sidebar .brand { padding:1.25rem 1.25rem; display:flex; align-items:center; gap:.65rem; color:#fff; }
        .ak-sidebar .brand .logo { width:38px;height:38px;border-radius:.6rem;background:linear-gradient(135deg,var(--ak-primary),var(--ak-primary-2));display:flex;align-items:center;justify-content:center;font-size:1.2rem; }
        .ak-sidebar .nav-section { font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7291;padding:.9rem 1.25rem .3rem; }
        .ak-sidebar .nav-link { color:#b9c0d4; padding:.6rem 1.25rem; display:flex; align-items:center; gap:.75rem; border-left:3px solid transparent; font-size:.925rem; }
        .ak-sidebar .nav-link:hover { color:#fff; background:rgba(255,255,255,.05); }
        .ak-sidebar .nav-link.active { color:#fff; background:rgba(79,70,229,.25); border-left-color:var(--ak-primary); }
        .ak-sidebar .nav-link i { width:1.2rem; text-align:center; font-size:1.05rem; }
        .ak-main { margin-left:260px; transition:margin .25s ease; }
        .ak-topbar { background:var(--bs-body-bg); border-bottom:1px solid var(--bs-border-color); position:sticky; top:0; z-index:1030; }
        .ak-content { padding:1.5rem; }
        .stat-card { border:none; border-radius:1rem; box-shadow:0 4px 20px rgba(0,0,0,.06); }
        [data-bs-theme="dark"] .stat-card { box-shadow:0 4px 20px rgba(0,0,0,.3); }
        .stat-icon { width:52px;height:52px;border-radius:.85rem;display:flex;align-items:center;justify-content:center;font-size:1.4rem; }
        .card { border:none; border-radius:1rem; box-shadow:0 4px 20px rgba(0,0,0,.05); }
        [data-bs-theme="dark"] .card { box-shadow:0 4px 20px rgba(0,0,0,.25); }
        .btn-ak { background:linear-gradient(135deg,var(--ak-primary),var(--ak-primary-2));border:none;color:#fff; }
        .btn-ak:hover { filter:brightness(1.08);color:#fff; }
        .avatar-sm { width:36px;height:36px;border-radius:50%;object-fit:cover; }
        @media (max-width:991.98px){
            .ak-sidebar { transform:translateX(-100%); }
            .ak-sidebar.show { transform:translateX(0); }
            .ak-main { margin-left:0; }
        }
    </style>
    @stack('head')
</head>
<body x-data="{ sidebar:false }">
    @include('partials.sidebar')

    <div class="ak-main">
        <div class="ak-topbar px-3 py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" @click="sidebar=!sidebar; document.querySelector('.ak-sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
                <h6 class="mb-0 fw-semibold d-none d-sm-block">@yield('page-title', 'Dashboard')</h6>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form action="{{ route('theme.toggle') }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" title="Toggle theme">
                        <i class="bi {{ request()->cookie('theme','light')==='dark' ? 'bi-sun' : 'bi-moon-stars' }}"></i>
                    </button>
                </form>
                <a href="{{ route('locale.switch', app()->getLocale()==='en' ? 'gu' : 'en') }}" class="btn btn-sm btn-outline-secondary" title="Language">
                    {{ strtoupper(app()->getLocale()) }}
                </a>
                <div class="dropdown">
                    <a class="btn btn-sm btn-outline-secondary position-relative" data-bs-toggle="dropdown" href="#">
                        <i class="bi bi-bell"></i>
                        @php($unread = auth()->user()->relationLoaded('notifications') ? 0 : \App\Models\InAppNotification::where('user_id',auth()->id())->whereNull('read_at')->count())
                        @if($unread)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unread }}</span>@endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:280px">
                        <h6 class="dropdown-header">Notifications</h6>
                        @forelse(\App\Models\InAppNotification::where('user_id',auth()->id())->latest()->limit(5)->get() as $n)
                            <div class="dropdown-item small text-wrap"><i class="bi {{ $n->icon ?? 'bi-info-circle' }} me-1"></i>{{ $n->title }}</div>
                        @empty
                            <div class="dropdown-item small text-muted">No notifications</div>
                        @endforelse
                    </div>
                </div>
                <div class="dropdown">
                    <a class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" href="#">
                        <img src="{{ auth()->user()->avatar_url }}" class="avatar-sm" alt="">
                        <span class="d-none d-md-inline small fw-semibold">{{ auth()->user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->getRoleNames()->implode(', ') }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        @can('manage settings')
                        <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        @endcan
                        <li><form action="{{ route('logout') }}" method="POST">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="ak-content">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @yield('content')
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) { navigator.serviceWorker.register('{{ route('pwa.sw') }}').catch(()=>{}); }
    </script>
    @stack('scripts')
</body>
</html>
