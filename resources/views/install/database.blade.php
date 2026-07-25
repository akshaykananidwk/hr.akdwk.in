@extends('install.layout')
@php($step=2)
@section('install-content')
    <h5 class="fw-semibold mb-3">Database Configuration</h5>
    <form method="POST" action="{{ route('install.database.save') }}" x-data="{ conn:'{{ old('db_connection','mysql') }}' }" x-init="conn=$refs.conn.value">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Connection</label>
            <select name="db_connection" class="form-select" x-ref="conn" @change="conn=$event.target.value">
                <option value="mysql" {{ old('db_connection')==='mysql'?'selected':'' }}>MySQL / MariaDB (recommended for hosting)</option>
                <option value="sqlite" {{ old('db_connection')==='sqlite'?'selected':'' }}>SQLite (zero-config)</option>
                <option value="pgsql" {{ old('db_connection')==='pgsql'?'selected':'' }}>PostgreSQL</option>
            </select>
        </div>
        <div x-show="conn!=='sqlite'">
            <div class="row g-2">
                <div class="col-8"><label class="form-label small fw-semibold">Host</label><input name="db_host" value="{{ old('db_host','127.0.0.1') }}" class="form-control" placeholder="localhost"></div>
                <div class="col-4"><label class="form-label small fw-semibold">Port</label><input name="db_port" value="{{ old('db_port','3306') }}" class="form-control"></div>
            </div>
            <div class="mb-2 mt-2"><label class="form-label small fw-semibold">Username</label><input name="db_username" value="{{ old('db_username') }}" class="form-control"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Password</label><input type="password" name="db_password" class="form-control"></div>
        </div>
        <div class="mb-3 mt-2">
            <label class="form-label small fw-semibold">Database <span x-show="conn==='sqlite'" class="text-muted">(file name)</span></label>
            <input name="db_database" value="{{ old('db_database','ak_workforce') }}" class="form-control" placeholder="ak_workforce" required>
            <small class="text-muted" x-show="conn!=='sqlite'">The database must already exist on your host.</small>
        </div>
        <button class="btn btn-ak w-100 py-2">Test &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </form>
@endsection
