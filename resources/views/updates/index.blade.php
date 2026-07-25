@extends('layouts.app')
@section('title', 'System Update')
@section('page-title', 'System Update')

@section('content')
<div class="row g-3" x-data="updater()">
    <!-- Configuration -->
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-github me-1"></i>GitHub Source</h6>
            <form method="POST" action="{{ route('updates.config') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Repository <span class="text-muted">(owner/repo)</span></label>
                    <input name="github_repo" value="{{ $config['repo'] }}" class="form-control" placeholder="akshaykananidwk/hr.akdwk.in" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Branch</label>
                    <input name="github_branch" value="{{ $config['branch'] }}" class="form-control" placeholder="main">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Access Token
                        @if($hasToken)<span class="badge bg-success">saved</span>@endif
                    </label>
                    <input type="password" name="github_token" class="form-control" placeholder="{{ $hasToken ? '•••••• (leave blank to keep)' : 'ghp_xxx (required for private repos)' }}">
                    <small class="text-muted">Stored encrypted. Needs <code>repo</code> scope for private repositories.</small>
                </div>
                <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-save me-1"></i>Save Settings</button>
            </form>
        </div></div>
    </div>

    <!-- Check / Update -->
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="fw-semibold mb-1"><i class="bi bi-cloud-arrow-down me-1"></i>Application Version</h6>
                    <small class="text-muted">Current: <code>{{ $config['version'] }}</code>
                        @if($config['current_commit']) · commit <code>{{ substr($config['current_commit'],0,7) }}</code>@endif
                    </small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" @click="check()" :disabled="loading">
                    <span x-show="!loading"><i class="bi bi-arrow-repeat me-1"></i>Check for Update</span>
                    <span x-show="loading"><span class="spinner-border spinner-border-sm"></span> Checking...</span>
                </button>
            </div>

            @unless($configured)
                <div class="alert alert-warning py-2 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Configure the GitHub repository first.</div>
            @endunless

            <!-- Result of check -->
            <template x-if="result && !result.update_available">
                <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>You are on the latest version.</div>
            </template>
            <template x-if="result && result.update_available">
                <div class="border rounded p-3 bg-body-tertiary">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-primary">Update available</span>
                        <code class="small" x-text="result.short_sha"></code>
                    </div>
                    <p class="mb-1 mt-2 fw-semibold" x-text="result.message?.split('\n')[0]"></p>
                    <small class="text-muted">by <span x-text="result.author"></span> · <span x-text="result.date ? new Date(result.date).toLocaleString() : ''"></span></small>
                    <hr>
                    <button class="btn btn-ak btn-sm w-100" @click="runUpdate()" :disabled="updating"
                            onclick="return confirm('This will back up the app, download the new version and run migrations. Continue?')">
                        <span x-show="!updating"><i class="bi bi-rocket-takeoff me-1"></i>Update Now</span>
                        <span x-show="updating"><span class="spinner-border spinner-border-sm"></span> Updating... do not close this page</span>
                    </button>
                </div>
            </template>

            <template x-if="error">
                <div class="alert alert-danger py-2 small mt-2" x-text="error"></div>
            </template>

            <!-- Live update log -->
            <template x-if="updateLog">
                <div class="mt-3">
                    <label class="form-label small fw-semibold">Update Log</label>
                    <pre class="bg-dark text-light p-3 rounded small" style="max-height:260px;overflow:auto" x-text="updateLog"></pre>
                    <template x-if="updateDone"><a href="{{ route('updates.index') }}" class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i>Done — Reload</a></template>
                </div>
            </template>
        </div></div>
    </div>

    <!-- History -->
    <div class="col-12">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-1"></i>Update History</h6>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>When</th><th>Commit</th><th>Status</th><th>By</th><th>Notes</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                        <td><code class="small">{{ $log->to_commit ? substr($log->to_commit,0,7) : '—' }}</code></td>
                        <td>
                            @php($map=['success'=>'success','failed'=>'danger','rolled_back'=>'warning'])
                            <span class="badge bg-{{ $map[$log->status] ?? 'secondary' }} text-capitalize">{{ str_replace('_',' ',$log->status) }}</span>
                        </td>
                        <td class="small">{{ $log->trigger?->name ?? 'System' }}</td>
                        <td class="small text-muted text-truncate" style="max-width:280px">{{ $log->error ?: \Illuminate\Support\Str::limit($log->commit_message, 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted small">No updates run yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updater() {
    return {
        loading:false, updating:false, result:null, error:null, updateLog:null, updateDone:false,
        token: document.querySelector('meta[name=csrf-token]').content,
        async check() {
            this.loading=true; this.error=null; this.result=null;
            try {
                const r = await fetch('{{ route('updates.check') }}', {method:'POST', headers:{'X-CSRF-TOKEN':this.token,'Accept':'application/json'}});
                const j = await r.json();
                if (j.ok) this.result = j.data; else this.error = j.message;
            } catch(e){ this.error='Network error while checking for updates.'; }
            this.loading=false;
        },
        async runUpdate() {
            this.updating=true; this.error=null; this.updateLog='Starting update...'; this.updateDone=false;
            try {
                const r = await fetch('{{ route('updates.run') }}', {method:'POST', headers:{'X-CSRF-TOKEN':this.token,'Accept':'application/json'}});
                const j = await r.json();
                this.updateLog = j.log || j.error || 'No output.';
                if (!j.ok && j.error) this.updateLog += "\n\nERROR: " + j.error;
                this.updateDone = true;
            } catch(e){ this.error='Network error during update. Check the update history.'; }
            this.updating=false;
        }
    }
}
</script>
@endpush
