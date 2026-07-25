@extends('layouts.app')
@section('title','Data Import')
@section('page-title','Import Legacy Data')
@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-1"><i class="bi bi-database-up me-1"></i>Import from Old Attendance System</h6>
            <p class="text-muted small">Upload your exported database (<code>.sql</code> or <code>.sql.zip</code>) from the old
            attendance system. Staff keep their existing <strong>phone number + password</strong>. Data maps automatically.</p>

            @if(session('import_summary'))
                @php($s = session('import_summary'))
                <div class="alert alert-success">
                    <strong><i class="bi bi-check-circle me-1"></i>Import complete!</strong>
                    <div class="row row-cols-2 row-cols-md-3 g-2 mt-1 small">
                        <div>Branches: <strong>{{ $s['branches'] }}</strong></div>
                        <div>Staff: <strong>{{ $s['users'] }}</strong></div>
                        <div>Attendance: <strong>{{ $s['attendance'] }}</strong></div>
                        <div>Leaves: <strong>{{ $s['leaves'] }}</strong></div>
                        <div>Announcements: <strong>{{ $s['announcements'] }}</strong></div>
                        <div>WhatsApp: <strong>{{ $s['whatsapp'] ? 'imported' : '—' }}</strong></div>
                        <div>Demo purged: <strong>{{ $s['demo_purged'] ? 'yes' : 'no' }}</strong></div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('import.run') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Database file (.sql or .zip)</label>
                    <input type="file" name="dump" class="form-control" accept=".sql,.txt,.zip" required>
                    <small class="text-muted">Max 50 MB.</small>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="purge_demo" value="1" class="form-check-input" id="purge" checked>
                    <label class="form-check-label small" for="purge">Delete demo data after import (recommended)</label>
                </div>
                <button class="btn btn-ak" onclick="return confirm('Import legacy data now? A backup of your current DB is recommended first.')">
                    <i class="bi bi-upload me-1"></i>Import Now
                </button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i>Status</h6>
            <ul class="list-unstyled small mb-0">
                <li class="mb-1">Imported staff: <strong>{{ $legacyUsers }}</strong></li>
                <li class="mb-1">Last import: <strong>{{ $lastImportedAt ? \Illuminate\Support\Carbon::parse($lastImportedAt)->diffForHumans() : 'never' }}</strong></li>
            </ul>
            <hr>
            <p class="small text-muted mb-1"><strong>What gets imported</strong></p>
            <ul class="small text-muted ps-3 mb-2">
                <li>Staff (phone + password kept)</li>
                <li>Branches</li>
                <li>Attendance (punch/lunch/out)</li>
                <li>Leave requests</li>
                <li>WhatsApp settings &amp; templates</li>
            </ul>
            <p class="small text-muted mb-0">Re-running is safe — records update by their original ID instead of duplicating.</p>
        </div></div>
    </div>
</div>
@endsection
