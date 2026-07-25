# Smart GitHub Auto-Update — Internals

Implemented in `app/Services/UpdateService.php`, exposed by `App\Http\Controllers\UpdateController`
at `/updates` (permission: `manage updates`).

## Configuration (stored in `settings`)

| Key | Meaning |
|-----|---------|
| `github_repo` | `owner/repo` |
| `github_branch` | branch to track (default `main`) |
| `github_token` | GitHub PAT — **encrypted** via Laravel `Crypt` |
| `current_commit` | SHA currently deployed |
| `app_version` | human version string |

Saved once via **Save Settings**; the token is only rewritten when a new value is entered.

## Check for Update

`GET https://api.github.com/repos/{repo}/commits/{branch}` with the bearer token.
Returns latest SHA, commit message, author and date. `update_available` is
`latest_sha !== current_commit`. HTTP 401/403/404 are surfaced as clear messages.

## Update Now — step machine

The `update_logs` row's `status` column advances through the pipeline so that a rollback
knows how far it got:

```
started → backed_up → downloaded → extracted → migrated → success
                                   └─ (on error) → rolled_back / failed
```

1. **checkForUpdate** — abort if already up to date.
2. **createBackup()** — zips the whole project **except** `.git`, `vendor`,
   `node_modules`, `storage`, `public/uploads`, `public/storage`
   → `storage/app/backups/backup_YYYYMMDD_His.zip`.
3. **backupDatabase()** — copies the SQLite file, or `mysqldump` for MySQL (best effort).
4. **downloadZipball()** — streams `.../zipball/{branch}` to `storage/app/updates/source.zip`.
5. **extractZip()** — unzips; GitHub wraps everything in one `owner-repo-sha/` folder.
6. **copyFiles()** — copies that folder over the project root, **skipping the protected
   paths** so user data/config is never touched:
   `.env`, `.git`, `storage`, `public/uploads`, `public/storage`, `node_modules`, `vendor`.
7. **maybeComposerInstall()** — runs `composer install --no-dev` only if the binary exists.
8. **migrate --force**, then **config/cache/route/view clear**.
9. **Setting::put('current_commit', latest)** and temp cleanup.

## Auto-rollback

If an exception is thrown **after** files were replaced (`extracted`/`migrated`), the
service:

1. Extracts the backup zip back over the project root (`restoreBackup`).
2. Restores the database (`restoreDatabase` — SQLite copy back; MySQL dump retained for
   manual restore).
3. Clears caches and marks the log `rolled_back` with the original error.

Failures before file replacement are simply marked `failed`; nothing was changed.

## What is preserved across updates

`.env`, `storage/` (logs, sessions, uploaded documents), `public/uploads`,
`public/storage`, the SQLite database file, and `vendor/` (unless you ship it in the
release). This means configuration and user data survive every update.

## Limitations & notes

- **Dependencies:** shared hosting usually can't run Composer. If a release changes
  `composer.json`, either commit `vendor/` to the release branch or run
  `composer install` over SSH after updating.
- The update runs inside a web request; `set_time_limit(0)` and a raised memory limit are
  applied. For very large repos prefer running the update from a shell:
  the same logic is reachable programmatically via `app(UpdateService::class)->performUpdate()`.
- Keep backups pruned — `storage/app/backups` grows one zip per update.
