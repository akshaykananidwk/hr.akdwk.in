<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install · AK Workforce Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:linear-gradient(135deg,#4f46e5,#7c3aed,#db2777); min-height:100vh; font-family:'Segoe UI',system-ui,sans-serif; padding:2rem 0; }
        .install-card { max-width:720px; margin:0 auto; border:none; border-radius:1.25rem; box-shadow:0 25px 60px rgba(0,0,0,.35); }
        .steps { display:flex; gap:.5rem; margin-bottom:1.5rem; }
        .steps .step { flex:1; text-align:center; padding:.5rem; border-radius:.6rem; font-size:.8rem; background:#eef; color:#667; }
        .steps .step.active { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; }
        .steps .step.done { background:#d1fae5; color:#065f46; }
        .btn-ak { background:linear-gradient(135deg,#4f46e5,#7c3aed); border:none; color:#fff; }
        .brand-badge { width:56px;height:56px;border-radius:.9rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto .75rem; }
    </style>
</head>
<body>
    <div class="install-card card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="brand-badge"><i class="bi bi-people-fill"></i></div>
            <h4 class="fw-bold mb-0">AK Workforce Pro</h4>
            <small class="text-muted">Installation Wizard</small>
        </div>

        @php($step = $step ?? 1)
        <div class="steps">
            <div class="step {{ $step==1?'active':($step>1?'done':'') }}">1 · Requirements</div>
            <div class="step {{ $step==2?'active':($step>2?'done':'') }}">2 · Database</div>
            <div class="step {{ $step==3?'active':($step>3?'done':'') }}">3 · Admin</div>
            <div class="step {{ $step==4?'active':'' }}">4 · Done</div>
        </div>

        @if(session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2 small"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        @yield('install-content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
