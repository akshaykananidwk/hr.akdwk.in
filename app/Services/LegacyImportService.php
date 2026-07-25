<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\EmployeeProfile;
use App\Models\Lead;
use App\Models\Leave;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Imports the legacy PHP attendance system (attendance.akdwk.in) from a
 * mysqldump .sql file into AK Workforce Pro.
 *
 * - Preserves bcrypt passwords (staff keep their current password).
 * - Login identifier is the phone number.
 * - Maps roles: admin→Super Admin, manager→Sales Manager, hr→HR Manager, staff→Employee.
 * - Idempotent via legacy_id, so re-running updates rather than duplicates.
 * - Optionally purges the demo data seeded at install.
 */
class LegacyImportService
{
    /** Demo business names created by DemoSeeder (for purge). */
    private const DEMO_BUSINESSES = [
        'Shreeji Restaurant', 'Patel Electronics', 'Gujarat Sweets', 'Metro Cafe', 'Krishna Traders',
        'Ganesh Mobiles', 'Royal Salon', 'Anand Dairy', 'Silver Spoon', 'Urban Kirana',
    ];

    private const ROLE_MAP = [
        'admin' => 'Super Admin',
        'manager' => 'Sales Manager',
        'hr' => 'HR Manager',
        'staff' => 'Employee',
    ];

    private array $summary = [];

    /**
     * Import from a .sql dump file. Returns a summary of what was imported.
     */
    public function importFromFile(string $path, array $options = [], ?int $actorId = null): array
    {
        $sql = file_get_contents($path);
        if ($sql === false || $sql === '') {
            throw new \RuntimeException('Could not read the SQL file.');
        }

        return $this->import($sql, $options, $actorId);
    }

    public function import(string $sql, array $options = [], ?int $actorId = null): array
    {
        $this->summary = ['branches' => 0, 'users' => 0, 'attendance' => 0, 'leaves' => 0, 'announcements' => 0, 'whatsapp' => false, 'demo_purged' => false];

        DB::transaction(function () use ($sql, $options, $actorId) {
            $this->importBranches($sql);
            $this->importUsers($sql);
            $this->importAttendance($sql);
            $this->importLeaves($sql);
            $this->importAnnouncements($sql, $actorId);
            $this->importWhatsappSettings($sql);

            if (! empty($options['purge_demo'])) {
                $this->purgeDemoData($actorId);
                $this->summary['demo_purged'] = true;
            }
        });

        return $this->summary;
    }

    // ---- Table importers -------------------------------------------------

    private function importBranches(string $sql): void
    {
        foreach ($this->rows($sql, 'branches') as $r) {
            Branch::updateOrCreate(
                ['legacy_id' => (int) $r['id']],
                [
                    'name' => $r['name'] ?: 'Branch '.$r['id'],
                    'address' => $r['address'] ?? null,
                    'is_active' => true,
                ]
            );
            $this->summary['branches']++;
        }
    }

    private function importUsers(string $sql): void
    {
        foreach ($this->rows($sql, 'users') as $r) {
            $legacyId = (int) $r['id'];
            $phone = preg_replace('/[^0-9]/', '', (string) ($r['phone_number'] ?? ''));
            $branch = $r['branch_id'] ? Branch::where('legacy_id', (int) $r['branch_id'])->first() : null;
            $role = self::ROLE_MAP[strtolower((string) ($r['role'] ?? 'staff'))] ?? 'Employee';

            // Synthesise an email (legacy has none); keep it stable per phone.
            $email = ($phone ?: 'staff'.$legacyId).'@attendance.akdwk.in';

            $status = ($r['account_status'] ?? 'active') === 'active' ? 'active' : 'inactive';
            $active = (int) ($r['login_allowed'] ?? 1) === 1 && $status === 'active';

            $user = User::withTrashed()->updateOrCreate(
                ['legacy_id' => $legacyId],
                [
                    'name' => $r['name'] ?: 'Staff '.$legacyId,
                    'phone' => $phone,
                    'email' => $email,
                    // 'hashed' cast keeps an existing bcrypt hash, hashes plain text.
                    'password' => $r['password'] ?: bin2hex(random_bytes(8)),
                    'branch_id' => $branch?->id,
                    'status' => $status,
                    'is_active' => $active,
                    'salary_type' => $r['salary_type'] ?? 'monthly',
                    'shift_start_time' => $r['shift_start_time'] ?? '09:30:00',
                    'whatsapp_opt_in' => (int) ($r['msg_active'] ?? 1) === 1,
                    'date_of_joining' => $this->date($r['join_date'] ?? null),
                    // Distinct prefix so imported staff never collide with the
                    // install admin's AK0001 code.
                    'employee_code' => 'EMP'.str_pad((string) $legacyId, 4, '0', STR_PAD_LEFT),
                    'deleted_at' => null,
                ]
            );
            $user->syncRoles([$role]);

            EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['basic_salary' => (float) ($r['salary_amount'] ?? 0)]
            );

            $this->summary['users']++;
        }
    }

    private function importAttendance(string $sql): void
    {
        // Map legacy user id -> new user id once.
        $userMap = User::withTrashed()->whereNotNull('legacy_id')->pluck('id', 'legacy_id');

        foreach ($this->rows($sql, 'attendance_logs') as $r) {
            $userId = $userMap[(int) $r['user_id']] ?? null;
            if (! $userId) {
                continue;
            }
            $date = $this->date($r['log_date'] ?? null);
            if (! $date) {
                continue;
            }

            $isLeave = (float) ($r['is_leave'] ?? 0) >= 1;
            $halfDay = (int) ($r['is_half_day'] ?? 0) === 1;
            $minutes = (int) ($r['total_worked_minutes'] ?? 0);

            Attendance::updateOrCreate(
                ['user_id' => $userId, 'date' => $date],
                [
                    'legacy_id' => (int) $r['id'],
                    'check_in_at' => $this->dateTime($date, $r['punch_in'] ?? null),
                    'lunch_out_at' => $this->dateTime($date, $r['lunch_out'] ?? null),
                    'lunch_in_at' => $this->dateTime($date, $r['lunch_in'] ?? null),
                    'check_out_at' => $this->dateTime($date, $r['punch_out'] ?? null),
                    'worked_minutes' => $minutes,
                    'working_hours' => round($minutes / 60, 2),
                    'method' => 'manual',
                    'status' => $isLeave ? 'leave' : ($halfDay ? 'half_day' : (! empty($r['punch_in']) ? 'present' : 'absent')),
                    'leave_reason' => $r['leave_reason'] ?? null,
                ]
            );
            $this->summary['attendance']++;
        }
    }

    private function importLeaves(string $sql): void
    {
        $userMap = User::withTrashed()->whereNotNull('legacy_id')->pluck('id', 'legacy_id');

        $typeMap = ['sick' => 'sick', 'medical' => 'medical', 'casual' => 'casual', 'emergency' => 'emergency'];
        $statusMap = ['approved' => 'approved', 'rejected' => 'rejected', 'pending' => 'pending', 'cancelled' => 'cancelled'];

        foreach ($this->rows($sql, 'leave_requests') as $r) {
            $userId = $userMap[(int) $r['user_id']] ?? null;
            if (! $userId) {
                continue;
            }
            $from = $this->date($r['start_date'] ?? null);
            $to = $this->date($r['end_date'] ?? null) ?: $from;
            if (! $from) {
                continue;
            }

            Leave::updateOrCreate(
                ['legacy_id' => (int) $r['id']],
                [
                    'user_id' => $userId,
                    'type' => $typeMap[strtolower((string) ($r['leave_type'] ?? 'casual'))] ?? 'casual',
                    'from_date' => $from,
                    'to_date' => $to,
                    'reason' => $r['reason'] ?? null,
                    'status' => $statusMap[strtolower((string) ($r['status'] ?? 'pending'))] ?? 'pending',
                ]
            );
            $this->summary['leaves']++;
        }
    }

    private function importAnnouncements(string $sql, ?int $actorId): void
    {
        foreach ($this->rows($sql, 'announcements') as $r) {
            Announcement::updateOrCreate(
                ['title' => $r['title'] ?: 'Announcement '.$r['id']],
                [
                    'body' => $r['content'] ?? '',
                    'created_by' => $actorId,
                    'is_active' => true,
                ]
            );
            $this->summary['announcements']++;
        }
    }

    private function importWhatsappSettings(string $sql): void
    {
        $rows = $this->rows($sql, 'settings');
        if (empty($rows)) {
            return;
        }
        $s = $rows[0];

        $map = [
            'whatsapp_api_key' => $s['api_key'] ?? '',
            'whatsapp_session_id' => $s['session_id'] ?? '',
            'whatsapp_group_id' => $s['group_id'] ?? '',
            'whatsapp_admin_mobile' => $s['admin_mobile'] ?? '',
            'whatsapp_msg_in' => $s['msg_in'] ?? null,
            'whatsapp_msg_out' => $s['msg_out'] ?? null,
            'whatsapp_msg_lunch_out' => $s['msg_lunch_out'] ?? null,
            'whatsapp_msg_lunch_in' => $s['msg_lunch_in'] ?? null,
            'whatsapp_msg_leave' => $s['msg_leave'] ?? null,
        ];
        foreach ($map as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::put($key, $value, 'whatsapp');
            }
        }
        // Enable WhatsApp automatically if we imported credentials.
        if (! empty($s['api_key']) && ! empty($s['session_id'])) {
            Setting::put('whatsapp_enabled', '1', 'whatsapp');
        }
        $this->summary['whatsapp'] = true;
    }

    // ---- Demo purge ------------------------------------------------------

    private function purgeDemoData(?int $actorId): void
    {
        // Demo users are the @akcomputer.in accounts seeded at install (no legacy_id),
        // excluding the current admin who triggered the import.
        User::withTrashed()
            ->where('email', 'like', '%@akcomputer.in')
            ->whereNull('legacy_id')
            ->when($actorId, fn ($q) => $q->where('id', '!=', $actorId))
            ->get()
            ->each(fn (User $u) => $u->forceDelete()); // hard delete → FK cascade removes their data

        // Demo leads + anything now orphaned by the user purge.
        Lead::whereIn('business_name', self::DEMO_BUSINESSES)->delete();
        Sale::whereNull('user_id')->delete();
        Commission::whereNull('user_id')->delete();
        Task::whereNull('assigned_to')->delete();
    }

    // ---- Date helpers ----------------------------------------------------

    private function date(?string $value): ?string
    {
        if (! $value || $value === '0000-00-00' || strtoupper($value) === 'NULL') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function dateTime(?string $date, ?string $time): ?string
    {
        if (! $date || ! $time || strtoupper((string) $time) === 'NULL') {
            return null;
        }
        try {
            return Carbon::parse($date.' '.$time)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ---- mysqldump parser ------------------------------------------------

    /**
     * Return all rows of a table as associative arrays keyed by column name.
     */
    public function rows(string $sql, string $table): array
    {
        $columns = $this->columns($sql, $table);
        if (empty($columns)) {
            return [];
        }

        $rows = [];
        foreach ($this->valueTuples($sql, $table) as $tuple) {
            $row = [];
            foreach ($columns as $i => $col) {
                $row[$col] = $tuple[$i] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Ordered column names from the CREATE TABLE statement.
     */
    public function columns(string $sql, string $table): array
    {
        if (! preg_match('/CREATE TABLE `'.preg_quote($table, '/').'` \((.*?)\n\)\s*ENGINE/s', $sql, $m)) {
            return [];
        }
        $cols = [];
        foreach (explode("\n", $m[1]) as $line) {
            $line = trim($line);
            if (preg_match('/^`([a-zA-Z0-9_]+)`\s/', $line, $mm)) {
                $cols[] = $mm[1];
            }
        }

        return $cols;
    }

    /**
     * Extract every value tuple for a table from its INSERT statements.
     * Returns an array of tuples, each tuple an ordered array of scalar values
     * (strings, or null for SQL NULL).
     */
    private function valueTuples(string $sql, string $table): array
    {
        $needle = 'INSERT INTO `'.$table.'`';
        $tuples = [];
        $offset = 0;

        while (($pos = strpos($sql, $needle, $offset)) !== false) {
            $valuesPos = strpos($sql, 'VALUES', $pos);
            if ($valuesPos === false) {
                break;
            }
            [$segment, $end] = $this->scanStatement($sql, $valuesPos + 6);
            $offset = $end;
            foreach ($this->splitTuples($segment) as $t) {
                $tuples[] = $t;
            }
        }

        return $tuples;
    }

    /**
     * Scan from $start until the statement-terminating ';' that is not inside a
     * quoted string. Returns [substring, endPosition].
     */
    private function scanStatement(string $sql, int $start): array
    {
        $len = strlen($sql);
        $i = $start;
        $inStr = false;
        $out = '';
        while ($i < $len) {
            $c = $sql[$i];
            if ($inStr) {
                if ($c === '\\') {
                    $out .= $c.($sql[$i + 1] ?? '');
                    $i += 2;

                    continue;
                }
                if ($c === "'") {
                    $inStr = false;
                }
            } elseif ($c === "'") {
                $inStr = true;
            } elseif ($c === ';') {
                return [$out, $i + 1];
            }
            $out .= $c;
            $i++;
        }

        return [$out, $len];
    }

    /**
     * Split "(a,b),(c,d)" into tuples of decoded scalar values.
     */
    private function splitTuples(string $segment): array
    {
        $rows = [];
        $len = strlen($segment);
        $i = 0;

        while ($i < $len) {
            // Advance to the next opening paren.
            while ($i < $len && $segment[$i] !== '(') {
                $i++;
            }
            if ($i >= $len) {
                break;
            }
            $i++; // skip '('

            $fields = [];
            $cur = '';
            $inStr = false;
            $wasQuoted = false;

            while ($i < $len) {
                $c = $segment[$i];

                if ($inStr) {
                    if ($c === '\\') {
                        $cur .= $this->unescape($segment[$i + 1] ?? '');
                        $i += 2;

                        continue;
                    }
                    if ($c === "'") {
                        $inStr = false;
                        $i++;

                        continue;
                    }
                    $cur .= $c;
                    $i++;

                    continue;
                }

                if ($c === "'") {
                    $inStr = true;
                    $wasQuoted = true;
                    $i++;

                    continue;
                }
                if ($c === ',') {
                    $fields[] = $this->finalizeField($cur, $wasQuoted);
                    $cur = '';
                    $wasQuoted = false;
                    $i++;

                    continue;
                }
                if ($c === ')') {
                    $fields[] = $this->finalizeField($cur, $wasQuoted);
                    $i++;
                    break;
                }
                $cur .= $c;
                $i++;
            }

            $rows[] = $fields;
        }

        return $rows;
    }

    private function finalizeField(string $raw, bool $wasQuoted): ?string
    {
        if ($wasQuoted) {
            return $raw;
        }
        $trimmed = trim($raw);
        if ($trimmed === '' || strtoupper($trimmed) === 'NULL') {
            return null;
        }

        return $trimmed;
    }

    private function unescape(string $c): string
    {
        return match ($c) {
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            '0' => "\0",
            default => $c, // \' \\ \" etc → literal char
        };
    }
}
