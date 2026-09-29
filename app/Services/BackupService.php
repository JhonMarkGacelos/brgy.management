<?php

namespace App\Services;

use App\Mail\BackupCreated;
use App\Models\Backup;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Database backups: a pure-PHP SQL dump (Laravel Cloud has no guaranteed mysqldump), stored privately on
 * Cloudinary because the server disk is wiped on every deploy, with an admin-set schedule.
 */
class BackupService
{
    /** Tables whose rows are temporary or secret; only their structure is backed up. */
    private const STRUCTURE_ONLY = [
        'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'login_otps', 'password_reset_tokens',
    ];

    private const INSERT_BATCH = 100;

    public const FREQUENCIES = ['off', 'daily', 'weekly', 'monthly'];

    public function __construct(private CloudinaryService $cloudinary) {}

    /** Take a backup and store it. Never throws: a failure is recorded as a failed Backup row. */
    public function run(string $trigger, ?User $by = null): Backup
    {
        $path = null;
        $filename = 'brgy-backup-' . now()->format('Y-m-d_His') . '.sql.gz';

        try {
            [$path, $tables, $rows] = $this->dumpSql();
            $upload = $this->cloudinary->uploadPrivateFile($path, $filename);

            $backup = Backup::create([
                'filename'   => $filename,
                'public_id'  => $upload['public_id'],
                'size'       => filesize($path),
                'tables'     => $tables,
                'rows'       => $rows,
                'trigger'    => $trigger,
                'status'     => 'success',
                'created_by' => $by?->id,
            ]);

            $this->emailCopy($backup, $path);
            $this->prune();

            return $backup;
        } catch (\Throwable $e) {
            Log::error('Database backup failed', ['trigger' => $trigger, 'error' => $e->getMessage()]);

            return Backup::create([
                'filename'   => $filename,
                'trigger'    => $trigger,
                'status'     => 'failed',
                'error'      => mb_substr($e->getMessage(), 0, 1000),
                'created_by' => $by?->id,
            ]);
        } finally {
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Write every table's structure and rows to a gzipped .sql file. Restore it into an empty database with
     * `gunzip -c file.sql.gz | psql "$DATABASE_URL"` (PostgreSQL) or `gunzip -c file.sql.gz | mysql dbname` (MySQL).
     * Returns [path, table count, row count].
     */
    public function dumpSql(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();
        $quoteId = fn (string $name) => $driver === 'mysql' ? '`' . str_replace('`', '``', $name) . '`' : '"' . str_replace('"', '""', $name) . '"';
        $schema = match ($driver) {
            'pgsql'  => $this->pgsqlSchema(),
            'sqlite' => $this->sqliteSchema(),
            default  => $this->mysqlSchema(),
        };

        $path = tempnam(sys_get_temp_dir(), 'brgy-backup-');
        $gz = gzopen($path, 'wb6');
        $tableCount = $rowCount = 0;
        $write = fn (array $statements) => $statements && gzwrite($gz, implode(";\n", $statements) . ";\n\n");

        gzwrite($gz, "-- Barangay Management System database backup ({$driver})\n-- Created " . now()->toDateTimeString() . ' (' . config('app.timezone') . ")\n\n");
        $write($schema['before']);

        foreach ($schema['tables'] as $table => $createSql) {
            $tableCount++;
            $name = $quoteId($table);
            $write($driver === 'pgsql' ? [$createSql] : ["DROP TABLE IF EXISTS {$name}", $createSql]);

            if (in_array($table, self::STRUCTURE_ONLY, true)) {
                continue;
            }

            $selectColumns = $schema['columns'][$table] ?? null;
            $columns = null;
            $insert = fn (array $batch) => gzwrite($gz, "INSERT INTO {$name} {$columns}" . (!empty($schema['identity'][$table]) ? ' OVERRIDING SYSTEM VALUE' : '') . " VALUES\n" . implode(",\n", $batch) . ";\n");
            $batch = [];
            foreach (($selectColumns ? DB::table($table)->select($selectColumns) : DB::table($table))->cursor() as $row) {
                $row = (array) $row;
                $columns ??= '(' . implode(', ', array_map($quoteId, array_keys($row))) . ')';
                $batch[] = '(' . implode(', ', array_map(fn ($v) => match (true) {
                    $v === null  => 'NULL',
                    is_bool($v)  => $driver === 'pgsql' ? ($v ? 'TRUE' : 'FALSE') : ($v ? '1' : '0'),
                    is_int($v), is_float($v) => (string) $v,
                    default      => $pdo->quote((string) $v),
                }, $row)) . ')';
                $rowCount++;

                if (count($batch) === self::INSERT_BATCH) {
                    $insert($batch);
                    $batch = [];
                }
            }
            if ($batch) {
                $insert($batch);
            }
            gzwrite($gz, "\n");
        }

        $write($schema['after']);
        gzclose($gz);

        return [$path, $tableCount, $rowCount];
    }

    private function mysqlSchema(): array
    {
        $tables = collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($t) => array_values((array) $t)[0])
            ->mapWithKeys(fn ($table) => [$table => DB::selectOne('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->{'Create Table'}])
            ->all();

        return ['before' => ['SET NAMES utf8mb4', 'SET FOREIGN_KEY_CHECKS=0'], 'tables' => $tables, 'after' => ['SET FOREIGN_KEY_CHECKS=1']];
    }

    private function sqliteSchema(): array
    {
        $tables = collect(DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->mapWithKeys(fn ($t) => [$t->name => $t->sql])->all();

        return ['before' => [], 'tables' => $tables, 'after' => []];
    }

    /**
     * PostgreSQL has no SHOW CREATE TABLE, so tables are rebuilt from the system catalog. Foreign keys and
     * indexes are added after the rows (so insert order doesn't matter), and ID sequences are moved past the
     * restored IDs so new records don't collide. The whole restore runs in one transaction.
     */
    private function pgsqlSchema(): array
    {
        $q = fn (string $name) => '"' . str_replace('"', '""', $name) . '"';
        $lit = fn (string $value) => "'" . str_replace("'", "''", $value) . "'";
        $regclass = "(quote_ident(current_schema()) || '.' || quote_ident(?))::regclass";
        $tables = collect(DB::select('SELECT tablename FROM pg_tables WHERE schemaname = current_schema() ORDER BY tablename'))->pluck('tablename');

        $before = ["SET client_encoding = 'UTF8'", 'SET standard_conforming_strings = on', 'BEGIN'];
        $after = $foreignKeys = $create = $columns = $identity = [];

        foreach ($tables as $table) {
            $before[] = "DROP TABLE IF EXISTS {$q($table)} CASCADE";
        }

        // Sequences behind serial/bigserial columns (identity columns recreate their own).
        $serials = DB::select("SELECT s.relname AS seq, t.relname AS tbl, a.attname AS col
            FROM pg_class s
            JOIN pg_namespace n ON n.oid = s.relnamespace AND n.nspname = current_schema()
            JOIN pg_depend d ON d.objid = s.oid AND d.classid = 'pg_class'::regclass AND d.deptype = 'a'
            JOIN pg_class t ON t.oid = d.refobjid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = d.refobjsubid
            WHERE s.relkind = 'S'");
        foreach ($serials as $s) {
            $before[] = "DROP SEQUENCE IF EXISTS {$q($s->seq)} CASCADE";
            $before[] = "CREATE SEQUENCE {$q($s->seq)}";
        }

        foreach ($tables as $table) {
            $lines = [];
            $cols = DB::select("SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS type, a.attnotnull, a.attidentity, a.attgenerated,
                    pg_get_expr(d.adbin, d.adrelid) AS def
                FROM pg_attribute a
                LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
                WHERE a.attrelid = {$regclass} AND a.attnum > 0 AND NOT a.attisdropped
                ORDER BY a.attnum", [$table]);

            foreach ($cols as $c) {
                $line = "{$q($c->attname)} {$c->type}";
                if ($c->attgenerated === 's') {
                    $line .= " GENERATED ALWAYS AS ({$c->def}) STORED";
                } elseif (in_array($c->attidentity, ['a', 'd'], true)) {
                    $line .= ' GENERATED ' . ($c->attidentity === 'a' ? 'ALWAYS' : 'BY DEFAULT') . ' AS IDENTITY';
                    $identity[$table] = true;
                    $after[] = "SELECT setval(pg_get_serial_sequence({$lit($q($table))}, {$lit($c->attname)}), COALESCE((SELECT MAX({$q($c->attname)}) FROM {$q($table)}), 1), (SELECT MAX({$q($c->attname)}) FROM {$q($table)}) IS NOT NULL)";
                } elseif ($c->def !== null) {
                    $line .= " DEFAULT {$c->def}";
                }
                if ($c->attnotnull) {
                    $line .= ' NOT NULL';
                }
                $lines[] = $line;
                if ($c->attgenerated !== 's') {
                    $columns[$table][] = $c->attname;
                }
            }

            $constraints = DB::select("SELECT conname, contype, pg_get_constraintdef(oid) AS def FROM pg_constraint
                WHERE conrelid = {$regclass} AND contype IN ('p', 'u', 'c', 'x', 'f') ORDER BY contype, conname", [$table]);
            foreach ($constraints as $con) {
                if ($con->contype === 'f') {
                    $foreignKeys[] = "ALTER TABLE {$q($table)} ADD CONSTRAINT {$q($con->conname)} {$con->def}";
                } else {
                    $lines[] = "CONSTRAINT {$q($con->conname)} {$con->def}";
                }
            }

            $create[$table] = "CREATE TABLE {$q($table)} (\n    " . implode(",\n    ", $lines) . "\n)";

            $indexes = DB::select("SELECT indexdef FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ?
                AND indexname NOT IN (SELECT conname FROM pg_constraint WHERE conrelid = {$regclass})", [$table, $table]);
            foreach ($indexes as $index) {
                $after[] = $index->indexdef;
            }
        }

        foreach ($serials as $s) {
            $after[] = "ALTER SEQUENCE {$q($s->seq)} OWNED BY {$q($s->tbl)}.{$q($s->col)}";
            $after[] = "SELECT setval({$lit($q($s->seq))}, COALESCE((SELECT MAX({$q($s->col)}) FROM {$q($s->tbl)}), 1), (SELECT MAX({$q($s->col)}) FROM {$q($s->tbl)}) IS NOT NULL)";
        }

        return [
            'before'   => $before,
            'tables'   => $create,
            'after'    => [...$after, ...$foreignKeys, 'COMMIT'],
            'columns'  => $columns,
            'identity' => $identity,
        ];
    }

    /** Email the backup as an attachment when the admin turned that on. A mail failure doesn't fail the backup. */
    private function emailCopy(Backup $backup, string $path): void
    {
        if (!Setting::get('backup_email_enabled') || !($to = Setting::get('backup_email'))) {
            return;
        }

        try {
            Mail::to($to)->send(new BackupCreated($backup, $path));
            $backup->update(['emailed_to' => $to]);
        } catch (\Throwable $e) {
            Log::error('Backup email failed', ['backup_id' => $backup->id, 'error' => $e->getMessage()]);
            $backup->update(['error' => 'Saved, but the email copy could not be sent: ' . mb_substr($e->getMessage(), 0, 500)]);
        }
    }

    /** Keep the newest N successful backups; older ones (and failures older than those) are removed. */
    public function prune(): void
    {
        $keep = $this->keepCount();
        $kept = Backup::where('status', 'success')->latest('id')->take($keep)->pluck('id');
        if ($kept->count() < $keep) {
            return;
        }

        Backup::where('id', '<', $kept->min())->get()->each(function (Backup $old) {
            $this->delete($old);
        });
    }

    public function delete(Backup $backup): void
    {
        if ($backup->public_id) {
            $this->cloudinary->deleteFile($backup->public_id);
        }
        $backup->delete();
    }

    public function keepCount(): int
    {
        return max(1, min(30, (int) Setting::get('backup_keep', 7)));
    }

    // ── Schedule ────────────────────────────────────────────────

    /**
     * Due when the latest scheduled time has passed and no scheduled backup has run since. Because it catches
     * up instead of needing the exact minute, a backup missed while the app was asleep runs on the next check.
     */
    public function isScheduledBackupDue(Carbon $now): bool
    {
        $slot = $this->lastSlot($now);
        if (!$slot) {
            return false;
        }

        // Only slots after the schedule was saved count, so turning it on doesn't fire for a time already past.
        $setAt = Setting::get('backup_schedule_set_at');
        if ($setAt && $slot->lt(Carbon::parse($setAt))) {
            return false;
        }

        return !Backup::where('trigger', 'scheduled')->where('status', 'success')->where('created_at', '>=', $slot)->exists();
    }

    /** When the next scheduled backup will run, or null when scheduling is off. */
    public function nextRunAt(?Carbon $now = null): ?Carbon
    {
        $now ??= now();
        if ($this->frequency() === 'off') {
            return null;
        }
        if ($this->isScheduledBackupDue($now)) {
            return $now;
        }

        $slot = $this->lastSlot($now) ?? $now;
        do {
            $slot = $this->advance($slot);
        } while ($slot->lte($now));

        return $slot;
    }

    public function frequency(): string
    {
        $frequency = Setting::get('backup_frequency', 'off');

        return in_array($frequency, self::FREQUENCIES, true) ? $frequency : 'off';
    }

    /** The most recent scheduled time at or before $now. */
    private function lastSlot(Carbon $now): ?Carbon
    {
        $frequency = $this->frequency();
        if ($frequency === 'off') {
            return null;
        }

        $now = $now->copy()->setTimezone(config('app.timezone'));
        $slot = $now->copy()->setTimeFromTimeString(Setting::get('backup_time', '02:00'))->second(0);

        if ($frequency === 'weekly') {
            $dow = (int) Setting::get('backup_day_of_week', 0);
            $slot->subDays(($slot->dayOfWeek - $dow + 7) % 7);
        } elseif ($frequency === 'monthly') {
            $slot->day((int) Setting::get('backup_day_of_month', 1));
        }

        return $slot->gt($now) ? $this->advance($slot, -1) : $slot;
    }

    private function advance(Carbon $slot, int $by = 1): Carbon
    {
        return match ($this->frequency()) {
            'weekly'  => $slot->copy()->addWeeks($by),
            'monthly' => $slot->copy()->addMonthsNoOverflow($by),
            default   => $slot->copy()->addDays($by),
        };
    }
}
