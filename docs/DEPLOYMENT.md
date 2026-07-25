# Deployment Guide — AK Workforce Pro

## 1. Requirements

- PHP **8.3+** with: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`,
  `ctype`, `json`, `curl`, `fileinfo`, `zip`, `gd`.
- MySQL 5.7+/MariaDB 10.3+ (or PostgreSQL / SQLite).
- Writable `storage/` and `bootstrap/cache/`.
- **No Node build required at runtime** — front-end libraries load from CDN.

## 2. Standard install (recommended)

1. Upload the project. Set the web server document root to **`public/`**.
2. `chmod -R 775 storage bootstrap/cache` (and correct ownership).
3. Create an empty MySQL database and user.
4. Open `https://your-domain/install` and complete the 4-step wizard.
5. Done — log in with the admin account you created.

## 3. Shared hosting where you *cannot* change the document root

If the domain must serve from `public_html/` directly:

**Option A (preferred):** put the Laravel app one level above `public_html`, then move the
contents of the app's `public/` into `public_html/` and edit the two paths in
`public_html/index.php`:

```php
require __DIR__.'/../ak-workforce/vendor/autoload.php';
$app = require_once __DIR__.'/../ak-workforce/bootstrap/app.php';
```

**Option B:** drop a `.htaccess` in the project root that rewrites everything into `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## 4. .env essentials

The installer writes these for you, but for reference:

```dotenv
APP_NAME="AK Workforce Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=ak_workforce
DB_USERNAME=ak_user
DB_PASSWORD=secret
```

After changing `.env` manually run `php artisan config:clear`.

## 5. Storage symlink

The installer runs `php artisan storage:link`. If public/storage is missing (some hosts
block symlinks), copy `storage/app/public` into `public/storage` or create the symlink via
your host's file manager.

## 6. Scheduler & queue (optional but recommended)

Add a cron entry for Laravel's scheduler:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

For background jobs (image compression, notifications) run a queue worker (Supervisor or a
cron-based `queue:work --stop-when-empty`).

## 7. Production cache (optional)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> The auto-updater clears these automatically after each update.

## 8. Post-install security

- Keep `storage/installed` in place (blocks the installer).
- Ensure `.env`, `/storage`, `/vendor` are not web-accessible (they aren't when docroot is `public/`).
- Rotate the GitHub token if it ever leaks (it is stored encrypted in `settings`).
