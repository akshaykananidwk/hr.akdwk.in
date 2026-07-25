<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ request()->cookie('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AK Workforce Pro')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --ak-primary:#4f46e5; --ak-primary-2:#7c3aed; }
        body {
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 50%,#db2777 100%);
            font-family:'Segoe UI',system-ui,sans-serif;
        }
        .auth-card {
            width:100%; max-width:440px; border:none; border-radius:1.25rem;
            box-shadow:0 25px 60px rgba(0,0,0,.35); backdrop-filter:blur(12px);
            background:rgba(255,255,255,.98);
        }
        [data-bs-theme="dark"] .auth-card { background:rgba(30,30,46,.98); }
        .brand-badge {
            width:64px;height:64px;border-radius:1rem;display:flex;align-items:center;justify-content:center;
            background:linear-gradient(135deg,var(--ak-primary),var(--ak-primary-2));color:#fff;font-size:1.75rem;
            margin:0 auto 1rem;box-shadow:0 10px 25px rgba(79,70,229,.4);
        }
        .btn-ak { background:linear-gradient(135deg,var(--ak-primary),var(--ak-primary-2));border:none;color:#fff; }
        .btn-ak:hover { filter:brightness(1.08);color:#fff; }
        .form-control:focus { border-color:var(--ak-primary); box-shadow:0 0 0 .2rem rgba(79,70,229,.2); }
    </style>
</head>
<body>
    <div class="auth-card card p-4 p-md-5 m-3">
        <div class="text-center mb-4">
            <div class="brand-badge"><i class="bi bi-people-fill"></i></div>
            <h4 class="fw-bold mb-0">{{ setting('company_name', 'AK Computer') }}</h4>
            <small class="text-muted">AK Workforce Pro</small>
        </div>
        @yield('content')
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
