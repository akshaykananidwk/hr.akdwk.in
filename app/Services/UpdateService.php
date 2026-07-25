<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\UpdateLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

/**
 * Smart self-update service.
 *
 * Once the GitHub repository, branch and token are saved, the app can:
 *   - check GitHub for a newer commit (checkForUpdate)
 *   - download & apply it in one click with automatic backup + rollback (performUpdate)
 *
 * User data (.env, /storage, /public/uploads, sqlite db) is never overwritten.
 */
class UpdateService
{
    /**
     * Paths (relative to project root) that must NEVER be overwritten by an update.
     */
    private array $protectedPaths = [
        '.env', '.git', 'storage', 'public/uploads', 'public/storage',
        'node_modules', 'vendor', // dependencies handled separately
    ];

    /**
     * Paths excluded from the file backup archive (they are large or user-data
     * we restore separately). storage is excluded to avoid archiving the backups.
     */
    private array $backupExcludes = [
        '.git', 'vendor', 'node_modules', 'storage', 'public/uploads', 'public/storage',
    ];

    public function config(): array
    {
        return [
            'repo' => Setting::get('github_repo'),
            'branch' => Setting::get('github_branch', 'main'),
            'token' => Setting::get('github_token'),
            'current_commit' => Setting::get('current_commit'),
            'version' => Setting::get('app_version', '1.0.0'),
        ];
    }

    public function isConfigured(): bool
    {
        $c = $this->config();

        return ! empty($c['repo']);
    }

    private function client()
    {
        $token = Setting::get('github_token');
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'AK-Workforce-Pro-Updater',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return Http::withHeaders($headers)->timeout(60);
    }

    /**
     * Query GitHub for the latest commit on the configured branch.
     */
    public function checkForUpdate(): array
    {
        $c = $this->config();
        if (empty($c['repo'])) {
            throw new RuntimeException('GitHub repository is not configured.');
        }

        $response = $this->client()->get("https://api.github.com/repos/{$c['repo']}/commits/{$c['branch']}");

        if ($response->status() === 404) {
            throw new RuntimeException('Repository or branch not found. Check the repo name, branch and token.');
        }
        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException('GitHub authentication failed. Check your access token.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('GitHub API error: HTTP '.$response->status());
        }

        $data = $response->json();
        $latestSha = $data['sha'] ?? null;

        return [
            'latest_commit' => $latestSha,
            'short_sha' => $latestSha ? substr($latestSha, 0, 7) : null,
            'message' => $data['commit']['message'] ?? '',
            'author' => $data['commit']['author']['name'] ?? 'Unknown',
            'date' => $data['commit']['author']['date'] ?? null,
            'url' => $data['html_url'] ?? null,
            'current_commit' => $c['current_commit'],
            'update_available' => $latestSha && $latestSha !== $c['current_commit'],
        ];
    }

    /**
     * Download and apply the latest code with backup + auto-rollback.
     */
    public function performUpdate(?int $userId = null): UpdateLog
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $c = $this->config();
        $steps = [];
        $log = UpdateLog::create([
            'from_commit' => $c['current_commit'],
            'status' => 'started',
            'triggered_by' => $userId,
        ]);

        $backupPath = null;

        try {
            $check = $this->checkForUpdate();
            $log->update([
                'to_commit' => $check['latest_commit'],
                'commit_message' => $check['message'],
            ]);

            if (! $check['update_available']) {
                throw new RuntimeException('Already up to date. Nothing to update.');
            }

            // 1) Backup application files.
            $steps[] = 'Creating file backup...';
            $backupPath = $this->createBackup();
            $log->update(['status' => 'backed_up', 'backup_path' => $backupPath, 'log' => implode("\n", $steps)]);

            // 2) Backup database (best effort).
            $steps[] = 'Backing up database...';
            $this->backupDatabase();

            // 3) Download the zipball.
            $steps[] = 'Downloading update package from GitHub...';
            $zipPath = $this->downloadZipball($c['repo'], $c['branch']);
            $log->update(['status' => 'downloaded', 'log' => implode("\n", $steps)]);

            // 4) Extract it.
            $steps[] = 'Extracting package...';
            $extractedRoot = $this->extractZip($zipPath);
            $log->update(['status' => 'extracted', 'log' => implode("\n", $steps)]);

            // 5) Copy files over the app, skipping protected paths.
            $steps[] = 'Applying new files (preserving .env, storage, uploads)...';
            $copied = $this->copyFiles($extractedRoot, base_path());
            $steps[] = "  → {$copied} files updated.";

            // 6) Update dependencies if composer is available (optional).
            $steps[] = $this->maybeComposerInstall();

            // 7) Run migrations.
            $steps[] = 'Running database migrations...';
            Artisan::call('migrate', ['--force' => true]);
            $steps[] = trim(Artisan::output());
            $log->update(['status' => 'migrated', 'log' => implode("\n", $steps)]);

            // 8) Clear caches.
            $steps[] = 'Clearing caches...';
            foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $cmd) {
                Artisan::call($cmd);
            }

            // 9) Record the new commit / version.
            Setting::put('current_commit', $check['latest_commit'], 'update');

            // 10) Cleanup temp files.
            $this->cleanupTemp();

            $steps[] = 'Update completed successfully.';
            $log->update(['status' => 'success', 'log' => implode("\n", $steps)]);

            ActivityLogger::log('system.update', $log, 'Applied update to '.$check['short_sha']);

            return $log;
        } catch (\Throwable $e) {
            $steps[] = 'ERROR: '.$e->getMessage();

            // Auto rollback if we already replaced files.
            if ($backupPath && in_array($log->fresh()->status, ['extracted', 'migrated'])) {
                $steps[] = 'Rolling back to previous version...';
                try {
                    $this->restoreBackup($backupPath);
                    $this->restoreDatabase();
                    foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $cmd) {
                        Artisan::call($cmd);
                    }
                    $steps[] = 'Rollback complete. Application restored.';
                    $log->update(['status' => 'rolled_back', 'error' => $e->getMessage(), 'log' => implode("\n", $steps)]);
                } catch (\Throwable $re) {
                    $steps[] = 'ROLLBACK FAILED: '.$re->getMessage();
                    $log->update(['status' => 'failed', 'error' => $e->getMessage()."\nRollback error: ".$re->getMessage(), 'log' => implode("\n", $steps)]);
                }
            } else {
                $log->update(['status' => 'failed', 'error' => $e->getMessage(), 'log' => implode("\n", $steps)]);
            }

            $this->cleanupTemp();

            return $log->fresh();
        }
    }

    // ---- Backup / restore -------------------------------------------------

    private function backupDir(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    private function createBackup(): string
    {
        $stamp = now()->format('Ymd_His');
        $path = $this->backupDir()."/backup_{$stamp}.zip";

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create backup archive.');
        }

        $base = base_path();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $realPath = $file->getPathname();
            $relative = ltrim(str_replace($base, '', $realPath), DIRECTORY_SEPARATOR);
            $relative = str_replace('\\', '/', $relative);

            if ($this->isExcluded($relative, $this->backupExcludes)) {
                continue;
            }
            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($realPath, $relative);
            }
        }
        $zip->close();

        return $path;
    }

    private function restoreBackup(string $backupPath): void
    {
        if (! file_exists($backupPath)) {
            throw new RuntimeException('Backup archive missing, cannot roll back.');
        }
        $zip = new ZipArchive;
        if ($zip->open($backupPath) !== true) {
            throw new RuntimeException('Could not open backup archive.');
        }
        $zip->extractTo(base_path());
        $zip->close();
    }

    private function backupDatabase(): void
    {
        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        if ($connection === 'sqlite') {
            $src = $db['database'];
            if (is_string($src) && file_exists($src)) {
                copy($src, $this->backupDir().'/database_backup.sqlite');
            }

            return;
        }

        if ($connection === 'mysql') {
            $file = $this->backupDir().'/database_backup.sql';
            $cmd = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s %s > %s 2>/dev/null',
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                $db['password'] ? '--password='.escapeshellarg($db['password']) : '',
                escapeshellarg($db['database']),
                escapeshellarg($file)
            );
            @exec($cmd, $out, $code);
            // Non-fatal if mysqldump is unavailable on the host.
        }
    }

    private function restoreDatabase(): void
    {
        $connection = config('database.default');
        if ($connection === 'sqlite') {
            $backup = $this->backupDir().'/database_backup.sqlite';
            $target = config('database.connections.sqlite.database');
            if (file_exists($backup) && is_string($target)) {
                copy($backup, $target);
            }
        }
        // For mysql, migrations are additive; the .sql dump is retained for manual restore.
    }

    // ---- Download / extract / copy ---------------------------------------

    private function downloadZipball(string $repo, string $branch): string
    {
        $dir = storage_path('app/updates');
        File::ensureDirectoryExists($dir);
        $zipPath = $dir.'/source.zip';

        $response = $this->client()->withOptions(['sink' => $zipPath])
            ->get("https://api.github.com/repos/{$repo}/zipball/{$branch}");

        if (! $response->successful() || ! file_exists($zipPath) || filesize($zipPath) < 100) {
            throw new RuntimeException('Failed to download update package (HTTP '.$response->status().').');
        }

        return $zipPath;
    }

    private function extractZip(string $zipPath): string
    {
        $target = storage_path('app/updates/extracted');
        File::deleteDirectory($target);
        File::ensureDirectoryExists($target);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open the downloaded package.');
        }
        $zip->extractTo($target);
        $zip->close();

        // GitHub zipballs contain a single top-level folder: {owner}-{repo}-{sha}.
        $dirs = File::directories($target);
        if (empty($dirs)) {
            throw new RuntimeException('Downloaded package is empty.');
        }

        return $dirs[0];
    }

    private function copyFiles(string $from, string $to): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relative = ltrim(str_replace($from, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace('\\', '/', $relative);

            if ($this->isExcluded($relative, $this->protectedPaths)) {
                continue;
            }

            $dest = $to.DIRECTORY_SEPARATOR.$relative;
            if ($file->isDir()) {
                File::ensureDirectoryExists($dest);
            } else {
                File::ensureDirectoryExists(dirname($dest));
                File::copy($file->getPathname(), $dest);
                $count++;
            }
        }

        return $count;
    }

    private function maybeComposerInstall(): string
    {
        // Only if a vendor dir wasn't shipped and composer is runnable (rare on shared hosting).
        $composer = trim((string) @shell_exec('which composer 2>/dev/null'));
        if ($composer === '') {
            return 'Skipped composer (not available on host — ship /vendor with the repo if dependencies changed).';
        }
        @exec('cd '.escapeshellarg(base_path()).' && '.escapeshellarg($composer).' install --no-dev --no-interaction 2>&1', $out, $code);

        return 'Composer install '.($code === 0 ? 'completed.' : 'skipped/failed (non-fatal).');
    }

    private function isExcluded(string $relative, array $list): bool
    {
        foreach ($list as $ex) {
            if ($relative === $ex || str_starts_with($relative, $ex.'/')) {
                return true;
            }
        }

        return false;
    }

    private function cleanupTemp(): void
    {
        File::deleteDirectory(storage_path('app/updates'));
    }

    public function saveConfig(string $repo, string $branch, ?string $token): void
    {
        Setting::put('github_repo', $repo, 'update');
        Setting::put('github_branch', $branch ?: 'main', 'update');
        if ($token !== null && $token !== '') {
            Setting::put('github_token', $token, 'update', encrypt: true);
        }
    }
}
