# Roadmap

The master brief describes a very large enterprise suite. This repository ships a
**production-ready foundation** — clean architecture, RBAC, the core HRMS + Field + CRM
loop, a web installer and a self-updater — that runs and can be extended module by module.

## ✅ Delivered (Phase 1)

- One-click `/install` wizard (requirements → DB → admin → migrate/seed → lock).
- Smart GitHub auto-update with backup + auto-rollback + history.
- Auth, remember-me, login/device/IP history, throttling.
- 9 roles + granular permissions (spatie).
- Employees, departments, designations, branches, HR profile.
- Versioned documents; digital policy acceptance (IP/device/geo logged).
- Attendance (GPS check-in/out, late/overtime, working hours).
- Daily reports with per-business visits + GPS.
- Leads/CRM pipeline, activity timeline, auto sale + commission on "won".
- Products & plans, sales list, commission wallet, targets, leaderboard.
- Tasks, leave apply/approve, announcements, activity audit log.
- Dashboard with role-aware widgets. Dark mode. EN + ગુજરાતી. PWA shell.
- Feature tests + Pint formatting.

## 🔜 Phase 2 — Payroll & Documents

- Salary slip generation (PDF) from basic + incentive + commission − deductions.
- Bank-transfer status, payroll runs, monthly registers.
- Document expiry reminders + approval workflow UI.
- Excel/PDF export across report modules.

## 🔜 Phase 3 — Communications & Field

- WhatsApp / SMS / Email notification drivers (queued).
- Auto follow-up reminders, target reminders.
- GPS route replay on a map (OpenStreetMap/Leaflet), geofence check.
- Voice note → text for daily reports.

## 🔜 Phase 4 — Enablement & API

- Training center: videos, PDFs, quiz/exam, certificate, progress.
- Customer feedback / rating capture.
- REST API + JWT (Sanctum/Passport) for a companion mobile app.

## 🔜 Phase 5 — Intelligence

- AI sales coach, AI report summaries, lead scoring/prediction,
  performance prediction, AI dashboard insights.

## Engineering conventions to keep

- Controllers thin; domain logic in `app/Services`.
- Form-request validation for larger forms.
- Policies/permissions checked per action.
- Migrations additive & reversible; seeders idempotent (`firstOrCreate`).
- Every state-changing action writes to the activity log.
