# AK Workforce Pro

**Enterprise Employee Management + HRMS + Sales CRM + Field Tracking + Daily Reporting + Payroll**, built for **AK Computer**.

A mobile-first, PWA-enabled Laravel application that manages the complete employee
lifecycle — from onboarding and digital agreements, through daily field reporting,
attendance, leads/CRM and commission, to a **one-click web installer** and a
**smart GitHub auto-update** system so the app can update itself in production.

> Built on **Laravel 13** (the current stable release; the original brief said
> Laravel 12 — the code only uses stable framework features that are identical across
> 12.x/13.x). PHP 8.3+.

---

## ✨ Highlights

| Area | What's included |
|------|-----------------|
| **One-click Installer** | Visit `/install` → requirements check → database → admin account → done. No manual `.env` editing. |
| **Smart Auto-Update** | Save GitHub repo + branch + token once. **Check for Update** shows the new commit (version, message, author, date). **Update Now** downloads it, backs up, runs migrations, clears cache — and **auto-rolls-back on error**. `.env`, `/storage`, uploads are never overwritten. |
| **Auth & RBAC** | Login, remember-me, device/IP history, throttling, 9 roles with granular permissions (spatie/laravel-permission). |
| **HRMS** | Employees, departments, designations, branches, HR profile, versioned documents, digital policy acceptance (IP + device + geo logged). |
| **Field Force** | Daily reports with per-business visits, GPS capture, attendance check-in/out with geolocation. |
| **Sales CRM** | Leads pipeline, activity timeline, products & plans, auto sale + commission on "won", targets & leaderboard. |
| **Ops** | Tasks, leave apply/approve, commission wallet, announcements, activity audit log. |
| **UX** | Bootstrap 5 + Alpine.js, dark mode, English + ગુજરાતી, PWA (installable + offline shell). |

---

## 🚀 Quick Start (local)

```bash
git clone <repo> ak-workforce && cd ak-workforce
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # or configure MySQL in .env
php artisan migrate --seed              # full dev seed (admin + demo data)
php artisan storage:link
php artisan serve
```

Then open <http://127.0.0.1:8000>.

**Default dev login** (from `DatabaseSeeder`):
`admin@akcomputer.in` / `password`

Other demo users (all password `password`): `manager@akcomputer.in`, `amit@akcomputer.in`, …

---

## 🌐 Production Install (shared hosting / cPanel) — no build step

The app uses **CDN assets** (Bootstrap 5, Bootstrap Icons, Alpine.js) so **no `npm run build`
is required on the server**. Just upload the files and open the installer.

1. Upload the project to your host. Point the domain at the **`public/`** folder.
2. Ensure `storage/` and `bootstrap/cache/` are writable (`755`/`775`).
3. Create an empty MySQL database + user in cPanel.
4. Browse to **`https://your-domain/install`** and follow the wizard:
   - **Requirements** — verifies PHP 8.3+, extensions, writable folders.
   - **Database** — enter DB host/name/user/pass; connection is tested live and written to `.env`.
   - **Admin** — company name + Super Admin account.
   - **Done** — migrations + seeders run automatically; a `storage/installed` lock is written.
5. Log in. No manual file editing at any step.

> Re-running `/install` is blocked while `storage/installed` exists.

See **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** for the "no docroot access" arrangement,
cron, queue and HTTPS notes.

---

## 🔄 Smart GitHub Auto-Update

Admin → **System Update** (`/updates`). Configure once:

- **Repository** — `owner/repo`
- **Branch** — e.g. `main`
- **Access Token** — a GitHub PAT with `repo` scope (stored **encrypted**). Needed for private repos.

Then:

- **Check for Update** — calls the GitHub API and shows the latest commit vs. your installed commit.
- **Update Now** — one click:
  1. **Backup** app files (zip) + database.
  2. **Download** the branch zipball from GitHub.
  3. **Apply** files, skipping `.env`, `/storage`, `/public/uploads`, `/vendor`.
  4. **Migrate** the database (`migrate --force`).
  5. **Clear** config/route/view/cache.
  6. On any error → **auto rollback** to the backup, DB restored.

Every run is written to the **Update History** table with status and log.

> **Dependencies:** shared hosting usually can't run Composer. If an update changes
> `composer` dependencies, commit the `vendor/` folder to the release branch, or run
> `composer install` via SSH. The updater attempts `composer install` automatically
> when the binary is available.

Full internals in **[docs/AUTO_UPDATE.md](docs/AUTO_UPDATE.md)**.

---

## 🧱 Architecture

```
app/
  Http/Controllers/      Install, Auth, Dashboard, Employee, Attendance,
                         DailyReport, Lead, Task, Leave, Product, Sales,
                         Commission, Settings, Update, Activity, System
  Http/Middleware/       EnsureInstalled, SetLocale
  Models/                26 Eloquent models with relationships & casts
  Services/              UpdateService (self-update), ActivityLogger
  Support/               helpers.php  (setting(), money(), app_installed())
database/
  migrations/            organization, users(+HR), HR, attendance/reports,
                         crm/sales, system tables
  seeders/               RolePermission, Settings, Organization, Demo
resources/views/         Bootstrap 5 Blade UI (layouts, install wizard, modules)
routes/web.php           all routes (62)
tests/Feature/           ApplicationSmokeTest (auth, RBAC, CRM, installer guard)
```

**Roles:** Super Admin · HR Manager · Sales Manager · Team Leader · Sales Executive ·
Support Executive · Accountant · Employee · Viewer.

---

## 🧪 Tests

```bash
php artisan test
```

Covers: login page, admin login → dashboard, roles/permissions seeded, RBAC denial,
lead creation, lead→sale→commission conversion, and the installer guard.

---

## 🗺️ Roadmap

The initial brief is very large. This repository delivers a **production-ready foundation**
covering the core workforce + CRM loop, the installer and the self-updater. Planned next
phases are tracked in **[docs/ROADMAP.md](docs/ROADMAP.md)** (payroll slip PDFs, WhatsApp/SMS
notifications, GPS route-replay maps, training center, REST/JWT API, quiz/exam, AI features).

---

## 🔐 Security

Laravel RBAC + policies, CSRF, hashed passwords, encrypted secrets (GitHub token),
rate-limited login, audit log, protected-path update engine, `.env`/uploads never
overwritten by updates. The install lock blocks re-install; restrict `/install` after go-live.

## License

Proprietary — © AK Computer.
